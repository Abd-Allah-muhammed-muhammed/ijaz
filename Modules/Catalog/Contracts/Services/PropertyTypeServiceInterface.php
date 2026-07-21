<?php

namespace Modules\Catalog\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Catalog\DTOs\StorePropertyTypeDTO;
use Modules\Catalog\DTOs\UpdatePropertyTypeDTO;
use Modules\Catalog\Models\PropertyType;
use Modules\Catalog\QueryFilters\PropertyType\PropertyTypeFilters;

interface PropertyTypeServiceInterface
{
    public function index(Request $request): LengthAwarePaginator;

    public function paginate(PropertyTypeFilters $filters): LengthAwarePaginator;

    public function store(StorePropertyTypeDTO $dto): PropertyType;

    public function update(PropertyType $propertyType, UpdatePropertyTypeDTO $dto): PropertyType;

    public function destroy(PropertyType $propertyType): void;

    public function updateStatus(PropertyType $propertyType, bool $isActive): PropertyType;

    public function show(PropertyType $propertyType): PropertyType;
}
