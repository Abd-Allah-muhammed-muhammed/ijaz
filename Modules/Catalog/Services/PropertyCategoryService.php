<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Catalog\QueryFilters\PropertyCategory\PropertyCategoryFilters;
use Modules\Catalog\Repositories\PropertyCategoryRepository;

class PropertyCategoryService
{
    public function __construct(private readonly PropertyCategoryRepository $repository) {}

    public function paginate(PropertyCategoryFilters $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }
}
