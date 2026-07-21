<?php

namespace Modules\Catalog\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\DTOs\StorePropertyCategoryDTO;
use Modules\Catalog\DTOs\UpdatePropertyCategoryDTO;
use Modules\Catalog\Models\PropertiyCategory;
use Modules\Catalog\QueryFilters\PropertyCategory\PropertyCategoryFilters;

interface PropertyCategoryServiceInterface
{
    public function index(Request $request): LengthAwarePaginator;

    public function paginate(PropertyCategoryFilters $filters): LengthAwarePaginator;

    public function store(StorePropertyCategoryDTO $dto): PropertiyCategory;

    public function update(PropertiyCategory $propertyCategory, UpdatePropertyCategoryDTO $dto): PropertiyCategory;

    public function destroy(PropertiyCategory $propertyCategory): void;

    public function show(PropertiyCategory $propertyCategory): PropertiyCategory;

    /**
     * @return Collection<int, PropertiyCategory>
     */
    public function getRootCategories(): Collection;
}
