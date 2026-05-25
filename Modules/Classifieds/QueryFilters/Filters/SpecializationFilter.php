<?php

namespace Modules\Classifieds\QueryFilters\Filters;

use Illuminate\Database\Eloquent\Builder;

final class SpecializationFilter
{
    public function __construct(
        private readonly ?int $value,
    ) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->value) {
            return $query;
        }

        return $query->where('specialization_id', $this->value);
    }
}
