<?php

namespace App\QueryFilters\PropertyType;

use App\Contracts\QueryFilters\QueryFilterInterface;
use App\QueryFilters\PropertyType\Filters\SearchFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PropertyTypeFilters
{
    public function __construct(private readonly Request $request) {}

    public function apply(Builder $query): Builder
    {
        foreach ($this->filters() as $filter) {
            $query = $filter->apply($query);
        }

        return $query;
    }

    public function perPage(): int
    {
        return $this->request->integer('per_page', 10);
    }

    /**
     * @return array<int, QueryFilterInterface>
     */
    private function filters(): array
    {
        return [
            new SearchFilter($this->request->filled('search') ? (string) $this->request->string('search') : null),
        ];
    }
}
