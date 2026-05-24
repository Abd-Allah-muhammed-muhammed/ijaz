<?php

namespace Modules\Classifieds\QueryFilters\Filters;

use Illuminate\Database\Eloquent\Builder;

final class YearRangeFilter
{
    public function __construct(
        private readonly ?int $minYear,
        private readonly ?int $maxYear,
    ) {}

    public function apply(Builder $query): Builder
    {
        if ($this->minYear) {
            $query = $query->where('year', '>=', $this->minYear);
        }

        if ($this->maxYear) {
            $query = $query->where('year', '<=', $this->maxYear);
        }

        return $query;
    }
}
