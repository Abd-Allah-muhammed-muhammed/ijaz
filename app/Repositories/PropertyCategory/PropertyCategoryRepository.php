<?php

namespace App\Repositories\PropertyCategory;

use App\Contracts\Repositories\PropertyCategory\PropertyCategoryRepositoryInterface;
use App\Models\PropertiyCategory;
use App\QueryFilters\PropertyCategory\PropertyCategoryFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PropertyCategoryRepository implements PropertyCategoryRepositoryInterface
{
    public function paginate(PropertyCategoryFilters $filters): LengthAwarePaginator
    {
        return $filters->apply(PropertiyCategory::query()->withCount(['children'])->with(['translations']))
            ->paginate($filters->perPage())
            ->withQueryString();
    }
}
