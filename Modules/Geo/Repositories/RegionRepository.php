<?php

namespace Modules\Geo\Repositories;

use App\Services\Normalize\Normalize;
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
            ->when($request->input('search'), function (Builder $query, mixed $value) {
                $normalized = Normalize::make((string) $value, app()->getLocale());

                return $query->whereTranslationLike('normalized_title', "%{$normalized}%");
            })
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
        return Region::query()
            ->with(['translation'])
            ->get();
    }
}
