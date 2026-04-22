<?php

namespace App\QueryFilters\PropertyAdvisement\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;

class OperationFilter implements QueryFilterInterface
{
    public function __construct(private readonly ?string $operation) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->operation) {
            return $query;
        }

        return $query->where('operation', $this->operation);
    }
}
