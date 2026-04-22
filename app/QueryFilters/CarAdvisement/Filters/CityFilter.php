<?php

namespace App\QueryFilters\CarAdvisement\Filters;

use Illuminate\Database\Eloquent\Builder;

final class CityFilter
{
    public function __construct(
        private readonly ?int $value,
    ) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->value) {
            return $query;
        }

        return $query->where('city_id', $this->value);
    }
}
