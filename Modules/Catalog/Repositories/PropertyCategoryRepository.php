<?php

namespace Modules\Catalog\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Catalog\Contracts\Repositories\PropertyCategoryRepositoryInterface;
use Modules\Catalog\Models\PropertiyCategory;
use Modules\Catalog\QueryFilters\PropertyCategory\PropertyCategoryFilters;

class PropertyCategoryRepository implements PropertyCategoryRepositoryInterface
{
    public function paginate(PropertyCategoryFilters $filters): LengthAwarePaginator
    {
        return $filters->apply(PropertiyCategory::query()->withCount(['children'])->with(['translations']))
            ->paginate($filters->perPage())
            ->withQueryString();
    }
}
