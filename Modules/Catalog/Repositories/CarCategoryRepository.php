<?php

namespace Modules\Catalog\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\CarCategoryRepositoryInterface;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\QueryFilters\CarCategory\CarCategoryFilters;

class CarCategoryRepository implements CarCategoryRepositoryInterface
{
    public function query(): Builder
    {
        return CarCategory::withCount(['children'])->with(['translation']);
    }

    public function paginate(Request $request): LengthAwarePaginator
    {
        $filters = new CarCategoryFilters($request);

        return $this->query()
            ->tap(fn (Builder $query) => $filters->apply($query))
            ->paginate($filters->perPage())
            ->withQueryString();
    }

    public function create(array $data): CarCategory
    {
        return CarCategory::create($data);
    }

    public function update(CarCategory $carCategory, array $data): CarCategory
    {
        $carCategory->update($data);

        return $carCategory->fresh();
    }

    public function delete(CarCategory $carCategory): void
    {
        if ($carCategory->children()->exists()) {
            throw new \Exception(__('this category has subcategories'));
        }
        $carCategory->deleteIcon();
        $carCategory->delete();
    }

    public function findById(int $id): CarCategory
    {
        return CarCategory::findOrFail($id);
    }

    /**
     * @return Collection<int, CarCategory>
     */
    public function getRootCategories(): Collection
    {
        return CarCategory::with(['translation'])->whereNull('parent_id')->get();
    }
}
