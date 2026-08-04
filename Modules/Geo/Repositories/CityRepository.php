<?php

namespace Modules\Geo\Repositories;

use App\Support\LookupCache;
use App\Support\TranslationSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Geo\Contracts\Repositories\CityRepositoryInterface;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

class CityRepository implements CityRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return City::query()
            ->with(['translation', 'region.translation'])
            ->when(
                $request->input('search'),
                fn (Builder $query, mixed $value) => TranslationSearch::apply($query, (string) $value)
            )
            ->when($request->input('region_id'), function (Builder $query, mixed $value) {
                return $query->where('region_id', $value);
            })
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function findById(int $id): City
    {
        return City::query()->findOrFail($id);
    }

    public function create(int $regionId, array $translations): City
    {
        return City::query()->create([
            'region_id' => $regionId,
            'translations' => $translations,
        ]);
    }

    public function update(City $city, int $regionId, array $translations): City
    {
        $city->update([
            'region_id' => $regionId,
            'translations' => $translations,
        ]);

        return $city->fresh(['translations', 'translation', 'region.translation']) ?? $city;
    }

    public function delete(City $city): void
    {
        $city->delete();
    }

    public function loadForEdit(City $city): City
    {
        return $city->load(['translations']);
    }

    /**
     * @return Collection<int, City>
     */
    public function listForSelect(?string $search = null, int $regionId = 0): Collection
    {
        if (filled($search)) {
            return City::query()->withTranslation()
                ->when($search, fn ($query, $v) => TranslationSearch::apply($query, (string) $v))
                ->when($regionId, fn ($query, $v) => $query->where('region_id', $v))
                ->get();
        }

        /** @var Collection<int, City> */
        return LookupCache::rememberForeverScoped(
            'cities:by-region',
            app()->getLocale(),
            $regionId,
            fn (): Collection => City::query()->withTranslation()
                ->when($regionId, fn ($query, $v) => $query->where('region_id', $v))
                ->get(),
        );
    }

    public function paginateForApiByRegion(Region $region, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return $region->cities()
            ->withTranslation()
            ->when($search, fn ($query, $v) => TranslationSearch::apply($query, (string) $v))
            ->paginate($perPage);
    }
}
