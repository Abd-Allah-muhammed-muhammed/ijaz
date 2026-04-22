<?php

namespace App\QueryFilters\CarAdvisement\Filters;

use Illuminate\Database\Eloquent\Builder;

final class CarCategoryFilter
{
    public function __construct(
        private readonly ?int $value,
    ) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->value) {
            return $query;
        }

        return $query->where('car_category_id', $this->value);
    }
}
