<?php

namespace Modules\Catalog\QueryFilters\PropertyCategory;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Catalog\QueryFilters\PropertyCategory\Filters\ParentFilter;
use Modules\Catalog\QueryFilters\PropertyCategory\Filters\SearchFilter;

class PropertyCategoryFilters
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
            new ParentFilter($this->request->integer('parent_id')),
        ];
    }
}
