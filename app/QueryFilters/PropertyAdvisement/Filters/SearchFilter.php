<?php

namespace App\QueryFilters\PropertyAdvisement\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;

class SearchFilter implements QueryFilterInterface
{
    public function __construct(private readonly ?string $search) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->search) {
            return $query;
        }

        return $query->where(function (Builder $nestedQuery) {
            $nestedQuery->where('title', 'like', "%{$this->search}%")
                ->orWhere('description', 'like', "%{$this->search}%");
        });
    }
}
