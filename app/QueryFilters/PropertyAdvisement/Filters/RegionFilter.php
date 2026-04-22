<?php

namespace App\QueryFilters\PropertyAdvisement\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;

class RegionFilter implements QueryFilterInterface
{
    public function __construct(private readonly ?int $regionId) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->regionId) {
            return $query;
        }

        return $query->where('region_id', $this->regionId);
    }
}
