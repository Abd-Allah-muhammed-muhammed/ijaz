<?php

namespace App\QueryFilters\CarAdvisement\Filters;

use Illuminate\Database\Eloquent\Builder;

final class CarBrandFilter
{
    public function __construct(
        private readonly ?int $value,
    ) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->value) {
            return $query;
        }

        return $query->where('car_brand_id', $this->value);
    }
}
