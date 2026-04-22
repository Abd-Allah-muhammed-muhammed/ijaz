<?php

namespace App\QueryFilters\CarAdvisement\Filters;

use Illuminate\Database\Eloquent\Builder;
use Stringable;

final class SearchFilter
{
    public function __construct(
        private readonly string|Stringable|null $value,
    ) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->value) {
            return $query;
        }

        $search = (string) $this->value;

        return $query->where(function (Builder $q) use ($search): void {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
