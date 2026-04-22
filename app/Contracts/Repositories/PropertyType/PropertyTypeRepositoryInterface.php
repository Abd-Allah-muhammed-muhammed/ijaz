<?php

namespace App\Contracts\Repositories\PropertyType;

use App\QueryFilters\PropertyType\PropertyTypeFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PropertyTypeRepositoryInterface
{
    public function paginate(PropertyTypeFilters $filters): LengthAwarePaginator;
}
