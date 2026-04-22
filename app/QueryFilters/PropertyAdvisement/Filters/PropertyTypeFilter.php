<?php

namespace App\QueryFilters\PropertyAdvisement\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;

class PropertyTypeFilter implements QueryFilterInterface
{
    public function __construct(private readonly ?int $propertyTypeId) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->propertyTypeId) {
            return $query;
        }

        return $query->where('property_type_id', $this->propertyTypeId);
    }
}
