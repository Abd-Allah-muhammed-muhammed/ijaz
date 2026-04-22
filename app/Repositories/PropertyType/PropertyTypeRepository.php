<?php

namespace App\Repositories\PropertyType;

use App\Contracts\Repositories\PropertyType\PropertyTypeRepositoryInterface;
use App\Models\PropertyType;
use App\QueryFilters\PropertyType\PropertyTypeFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PropertyTypeRepository implements PropertyTypeRepositoryInterface
{
    public function paginate(PropertyTypeFilters $filters): LengthAwarePaginator
    {
        return $filters->apply(PropertyType::query()->with(['translations']))
            ->paginate($filters->perPage())
            ->withQueryString();
    }
}
