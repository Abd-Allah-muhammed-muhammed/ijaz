<?php

namespace App\Contracts\Repositories\PropertyCategory;

use App\QueryFilters\PropertyCategory\PropertyCategoryFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PropertyCategoryRepositoryInterface
{
    public function paginate(PropertyCategoryFilters $filters): LengthAwarePaginator;
}
