<?php

namespace App\QueryFilters\PropertyAdvisement\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;

class CityFilter implements QueryFilterInterface
{
    public function __construct(private readonly ?int $cityId) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->cityId) {
            return $query;
        }

        return $query->where('city_id', $this->cityId);
    }
}
