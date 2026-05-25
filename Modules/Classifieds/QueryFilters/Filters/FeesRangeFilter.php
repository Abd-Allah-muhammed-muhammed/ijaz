<?php

namespace Modules\Classifieds\QueryFilters\Filters;

use Illuminate\Database\Eloquent\Builder;

final class FeesRangeFilter
{
    public function __construct(
        private readonly ?float $minFees,
        private readonly ?float $maxFees,
    ) {}

    public function apply(Builder $query): Builder
    {
        if ($this->minFees !== null) {
            $query = $query->where(function (Builder $q): void {
                $q->where('fees_from', '>=', $this->minFees)
                    ->orWhere('fees_to', '>=', $this->minFees);
            });
        }

        if ($this->maxFees !== null) {
            $query = $query->where(function (Builder $q): void {
                $q->where('fees_to', '<=', $this->maxFees)
                    ->orWhere('fees_from', '<=', $this->maxFees);
            });
        }

        return $query;
    }
}
