<?php

namespace Modules\Catalog\QueryFilters\Specialization;

use App\Contracts\QueryFilters\QueryFilterInterface;
use App\Services\Normalize\Normalize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Catalog\QueryFilters\Specialization\Filters\ParentFilter;
use Modules\Catalog\QueryFilters\Specialization\Filters\SearchFilter;

class SpecializationFilters
{
    public function __construct(
        private readonly Request $request,
    ) {}

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
            new SearchFilter(
                $this->request->filled('search') ? Normalize::make($this->request->string('search'), app()->getLocale()) : null
            ),
            new ParentFilter($this->request->integer('parent_id')),
        ];
    }
}
