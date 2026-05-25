<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Catalog\QueryFilters\PropertyType\PropertyTypeFilters;

interface PropertyTypeRepositoryInterface
{
    public function paginate(PropertyTypeFilters $filters): LengthAwarePaginator;
}
