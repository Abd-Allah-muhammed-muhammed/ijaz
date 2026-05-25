<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Catalog\QueryFilters\PropertyCategory\PropertyCategoryFilters;

interface PropertyCategoryRepositoryInterface
{
    public function paginate(PropertyCategoryFilters $filters): LengthAwarePaginator;
}
