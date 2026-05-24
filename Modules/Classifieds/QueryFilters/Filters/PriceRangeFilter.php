<?php

namespace Modules\Classifieds\QueryFilters\Filters;

use Illuminate\Database\Eloquent\Builder;

final class PriceRangeFilter
{
    public function __construct(
        private readonly ?float $minPrice,
        private readonly ?float $maxPrice,
    ) {}

    public function apply(Builder $query): Builder
    {
        if ($this->minPrice) {
            $query = $query->where('price', '>=', $this->minPrice);
        }

        if ($this->maxPrice) {
            $query = $query->where('price', '<=', $this->maxPrice);
        }

        return $query;
    }
}
