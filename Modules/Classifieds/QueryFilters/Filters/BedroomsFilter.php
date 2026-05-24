<?php

namespace Modules\Classifieds\QueryFilters\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;

class BedroomsFilter implements QueryFilterInterface
{
    public function __construct(private readonly ?int $bedroomsCount) {}

    public function apply(Builder $query): Builder
    {
        if ($this->bedroomsCount === null) {
            return $query;
        }

        return $query->where('bedrooms_count', $this->bedroomsCount);
    }
}
