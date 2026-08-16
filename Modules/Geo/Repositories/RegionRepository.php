<?php

namespace Modules\Geo\Repositories;

use App\Support\LookupCache;
use App\Support\TranslationSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;
use Modules\Geo\Models\Region;

class RegionRepository implements RegionRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return Region::query()
            ->with(['translation'])
            ->withCount(['cities'])
            ->when(
                $request->input('search'),
                fn (Builder $query, mixed $value) => TranslationSearch::apply($query, (string) $value)
            )
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function findById(int $id): Region
    {
        return Region::query()->findOrFail($id);
    }

    public function create(array $translations): Region
    {
        return Region::query()->create([
            'translations' => $translations,
        ]);
    }

    public function update(Region $region, array $translations): Region
    {
        $region->update([
            'translations' => $translations,
        ]);

        return $region->fresh(['translations', 'translation']) ?? $region;
    }

    public function delete(Region $region): void
    {
        $region->delete();
    }

    public function loadForEdit(Region $region): Region
    {
        return $region->load(['translations']);
    }

    /**
     * @return Collection<int, Region>
     */
    public function getAllForDropdown(): Collection
    {
        /** @var Collection<int, Region> */
        return LookupCache::rememberForeverForLocale(
            'regions:dropdown',
            app()->getLocale(),
            fn (): Collection => Region::query()
                ->with(['translation'])
                ->get(),
        );
    }

    /**
     * @return Collection<int, Region>
     */
    public function listForSelect(?string $search = null): Collection
    {
        if (filled($search)) {
            return Region::query()->withTranslation()
                ->when($search, fn ($query, $v) => TranslationSearch::apply($query, (string) $v))
                ->get();
        }

        /** @var Collection<int, Region> */
        return LookupCache::rememberForeverForLocale(
            'regions:all',
            app()->getLocale(),
            fn (): Collection => Region::query()->withTranslation()->get(),
        );
    }

    public function paginateForApi(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return Region::query()
            ->withTranslation()
            ->when($search, fn ($query, $v) => TranslationSearch::apply($query, (string) $v))
            ->paginate($perPage);
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Region>
     */
    public function listByIds(array $ids): Collection
    {
        /** @var Collection<int, Region> */
        return Region::query()
            ->with(['translations'])
            ->withCount('cities')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get();
    }
}
