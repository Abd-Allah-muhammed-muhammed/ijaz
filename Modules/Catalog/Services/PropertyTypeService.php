<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Catalog\QueryFilters\PropertyType\PropertyTypeFilters;
use Modules\Catalog\Repositories\PropertyTypeRepository;

class PropertyTypeService
{
    public function __construct(private readonly PropertyTypeRepository $repository) {}

    public function paginate(PropertyTypeFilters $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }
}
