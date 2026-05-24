<?php

namespace Modules\Classifieds\QueryFilters\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;

class StatusFilter implements QueryFilterInterface
{
    public function __construct(private readonly ?string $status) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->status) {
            return $query;
        }

        return $query->where('status', $this->status);
    }
}
