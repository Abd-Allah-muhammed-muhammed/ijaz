<?php

namespace Modules\Catalog\Repositories;

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
