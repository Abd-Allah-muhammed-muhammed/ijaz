<?php

namespace App\Repositories;

use App\Contracts\Repositories\CarCategoryRepositoryInterface;
use App\Models\CarCategory;
use App\QueryFilters\CarCategory\CarCategoryFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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
}
