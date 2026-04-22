<?php

namespace App\QueryFilters\PropertyAdvisement\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;

class AreaFilter implements QueryFilterInterface
{
    public function __construct(
        private readonly ?float $minArea,
        private readonly ?float $maxArea,
    ) {}

    public function apply(Builder $query): Builder
    {
        if ($this->minArea !== null) {
            $query->where('area', '>=', $this->minArea);
        }

        if ($this->maxArea !== null) {
            $query->where('area', '<=', $this->maxArea);
        }

        return $query;
    }
}
