<?php

namespace Modules\Catalog\Repositories;

use App\Services\Normalize\Normalize;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\DeviceCategoryRepositoryInterface;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\QueryFilters\DeviceCategory\DeviceCategoryFilters;

class DeviceCategoryRepository implements DeviceCategoryRepositoryInterface
{
    public function query(): Builder
    {
        return DeviceCategory::withCount(['children'])->with(['translation']);
    }

    public function paginate(Request $request): LengthAwarePaginator
    {
        $filters = new DeviceCategoryFilters($request);

        return $this->query()
            ->tap(fn (Builder $query) => $filters->apply($query))
            ->paginate($filters->perPage())
            ->withQueryString();
    }

    /**
     * @return Collection<int, DeviceCategory>
     */
    public function getAll(Request $request): Collection
    {
        return DeviceCategory::with(['translation'])
            ->when($request->parent_id,
                fn (Builder $query, mixed $value) => $query->where('parent_id', $value),
                fn (Builder $query) => $query->whereNull('parent_id'))
            ->when($request->search, function (Builder $query, mixed $value) {
                $normalized = Normalize::make($value, app()->getLocale())->toString();

                return $query->whereTranslationLike('normalized_title', "%{$normalized}%");
            })
            ->get();
    }

    public function create(array $data): DeviceCategory
    {
        return DeviceCategory::create($data);
    }

    public function update(DeviceCategory $deviceCategory, array $data): DeviceCategory
    {
        $deviceCategory->update($data);

        return $deviceCategory->fresh();
    }

    public function delete(DeviceCategory $deviceCategory): void
    {
        if ($deviceCategory->children()->exists()) {
            throw new \Exception(__('this category has subcategories'));
        }
        $deviceCategory->deleteIcon();
        $deviceCategory->delete();
    }

    public function findById(int $id): DeviceCategory
    {
        return DeviceCategory::findOrFail($id);
    }

    /**
     * @return Collection<int, DeviceCategory>
     */
    public function getRootCategories(?int $excludeId = null): Collection
    {
        return DeviceCategory::with(['translation'])
            ->whereNull('parent_id')
            ->when($excludeId, fn (Builder $query) => $query->where('id', '!=', $excludeId))
            ->get();
    }
}
