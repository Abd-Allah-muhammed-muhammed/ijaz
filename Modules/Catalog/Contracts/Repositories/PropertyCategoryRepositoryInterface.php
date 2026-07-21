<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Models\PropertiyCategory;
use Modules\Catalog\QueryFilters\PropertyCategory\PropertyCategoryFilters;

interface PropertyCategoryRepositoryInterface
{
    public function paginate(PropertyCategoryFilters $filters): LengthAwarePaginator;

    public function paginateForDashboard(Request $request): LengthAwarePaginator;

    public function findById(int $id): PropertiyCategory;

    public function create(array $data): PropertiyCategory;

    public function update(PropertiyCategory $propertyCategory, array $data): PropertiyCategory;

    public function delete(PropertiyCategory $propertyCategory): void;

    public function loadForEdit(PropertiyCategory $propertyCategory): PropertiyCategory;

    /**
     * @return Collection<int, PropertiyCategory>
     */
    public function getRootCategories(): Collection;
}
