<?php

namespace App\QueryFilters\CarAdvisement\Filters;

use Illuminate\Database\Eloquent\Builder;
use Stringable;

final class UsageStatusFilter
{
    public function __construct(
        private readonly string|Stringable|null $value,
    ) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->value) {
            return $query;
        }

        return $query->where('usage_status', (string) $this->value);
    }
}
