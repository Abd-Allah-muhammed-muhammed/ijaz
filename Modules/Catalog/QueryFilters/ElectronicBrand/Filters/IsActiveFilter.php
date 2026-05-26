<?php

namespace Modules\Catalog\QueryFilters\ElectronicBrand\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;

class IsActiveFilter implements QueryFilterInterface
{
    public function __construct(private readonly ?bool $isActive) {}

    public function apply(Builder $query): Builder
    {
        if ($this->isActive === null) {
            return $query;
        }

        return $query->where('is_active', $this->isActive);
    }
}
