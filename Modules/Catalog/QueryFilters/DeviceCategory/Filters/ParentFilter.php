<?php

namespace Modules\Catalog\QueryFilters\DeviceCategory\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;

class ParentFilter implements QueryFilterInterface
{
    public function __construct(private readonly ?int $parentId) {}

    public function apply(Builder $query): Builder
    {
        if ($this->parentId) {
            return $query->where('parent_id', $this->parentId);
        }

        return $query->whereNull('parent_id');
    }
}
