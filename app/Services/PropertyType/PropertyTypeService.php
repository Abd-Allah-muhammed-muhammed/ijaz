<?php

namespace App\Services\PropertyType;

use App\QueryFilters\PropertyType\PropertyTypeFilters;
use App\Repositories\PropertyType\PropertyTypeRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PropertyTypeService
{
    public function __construct(private readonly PropertyTypeRepository $repository) {}

    public function paginate(PropertyTypeFilters $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }
}
