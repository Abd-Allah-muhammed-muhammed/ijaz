<?php

namespace Modules\Catalog\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\DTOs\StorePropertyCategoryDTO;
use Modules\Catalog\DTOs\UpdatePropertyCategoryDTO;
use Modules\Catalog\Models\PropertyCategory;
use Modules\Catalog\QueryFilters\PropertyCategory\PropertyCategoryFilters;

interface PropertyCategoryServiceInterface
{
    public function index(Request $request): LengthAwarePaginator;

    public function paginate(PropertyCategoryFilters $filters): LengthAwarePaginator;

    public function store(StorePropertyCategoryDTO $dto): PropertyCategory;

    public function update(PropertyCategory $propertyCategory, UpdatePropertyCategoryDTO $dto): PropertyCategory;

    public function destroy(PropertyCategory $propertyCategory): void;

    public function show(PropertyCategory $propertyCategory): PropertyCategory;

    /**
     * @return Collection<int, PropertyCategory>
     */
    public function getRootCategories(): Collection;

    /**
     * @return Collection<int, PropertyCategory>
     */
    public function listForSelect(?string $search = null): Collection;
}
