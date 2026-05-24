<?php

namespace Modules\Classifieds\QueryFilters\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;

class PriceFilter implements QueryFilterInterface
{
    public function __construct(
        private readonly ?float $minPrice,
        private readonly ?float $maxPrice,
    ) {}

    public function apply(Builder $query): Builder
    {
        if ($this->minPrice !== null) {
            $query->where('price', '>=', $this->minPrice);
        }

        if ($this->maxPrice !== null) {
            $query->where('price', '<=', $this->maxPrice);
        }

        return $query;
    }
}
