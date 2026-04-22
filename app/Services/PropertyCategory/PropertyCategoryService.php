<?php

namespace App\Services\PropertyCategory;

use App\QueryFilters\PropertyCategory\PropertyCategoryFilters;
use App\Repositories\PropertyCategory\PropertyCategoryRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PropertyCategoryService
{
    public function __construct(private readonly PropertyCategoryRepository $repository) {}

    public function paginate(PropertyCategoryFilters $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }
}
