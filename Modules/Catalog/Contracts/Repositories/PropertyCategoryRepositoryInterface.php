<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Models\PropertyCategory;
use Modules\Catalog\QueryFilters\PropertyCategory\PropertyCategoryFilters;

interface PropertyCategoryRepositoryInterface
{
    public function paginate(PropertyCategoryFilters $filters): LengthAwarePaginator;

    public function paginateForDashboard(Request $request): LengthAwarePaginator;

    public function findById(int $id): PropertyCategory;

    public function create(array $data): PropertyCategory;

    public function update(PropertyCategory $propertyCategory, array $data): PropertyCategory;

    public function delete(PropertyCategory $propertyCategory): void;

    public function loadForEdit(PropertyCategory $propertyCategory): PropertyCategory;

    /**
     * @return Collection<int, PropertyCategory>
     */
    public function getRootCategories(): Collection;
}
