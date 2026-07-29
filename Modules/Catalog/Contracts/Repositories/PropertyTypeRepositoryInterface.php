<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Catalog\Models\PropertyType;
use Modules\Catalog\QueryFilters\PropertyType\PropertyTypeFilters;

interface PropertyTypeRepositoryInterface
{
    public function paginate(PropertyTypeFilters $filters): LengthAwarePaginator;

    public function paginateForDashboard(Request $request): LengthAwarePaginator;

    public function findById(int $id): PropertyType;

    public function create(array $data): PropertyType;

    public function update(PropertyType $propertyType, array $data): PropertyType;

    public function delete(PropertyType $propertyType): void;

    public function loadForEdit(PropertyType $propertyType): PropertyType;
}
