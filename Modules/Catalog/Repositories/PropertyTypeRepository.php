<?php

namespace Modules\Catalog\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Catalog\Contracts\Repositories\PropertyTypeRepositoryInterface;
use Modules\Catalog\Models\PropertyType;
use Modules\Catalog\QueryFilters\PropertyType\PropertyTypeFilters;

class PropertyTypeRepository implements PropertyTypeRepositoryInterface
{
    public function paginate(PropertyTypeFilters $filters): LengthAwarePaginator
    {
        return $filters->apply(PropertyType::query()->with(['translations']))
            ->paginate($filters->perPage())
            ->withQueryString();
    }
}
