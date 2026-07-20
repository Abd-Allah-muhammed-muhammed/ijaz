<?php

namespace Modules\Geo\Repositories;

use App\Services\Normalize\Normalize;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Geo\Contracts\Repositories\CityRepositoryInterface;
use Modules\Geo\Models\City;

class CityRepository implements CityRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return City::query()
            ->with(['translation', 'region.translation'])
            ->when($request->input('search'), function (Builder $query, mixed $value) {
                $normalized = Normalize::make((string) $value, app()->getLocale());

                return $query->whereTranslationLike('normalized_title', "%{$normalized}%");
            })
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
}
