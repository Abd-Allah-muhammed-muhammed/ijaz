<?php

namespace Modules\Catalog\QueryFilters\ElectronicBrand;

use App\Contracts\QueryFilters\QueryFilterInterface;
use App\Services\Normalize\Normalize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Catalog\QueryFilters\ElectronicBrand\Filters\IsActiveFilter;
use Modules\Catalog\QueryFilters\ElectronicBrand\Filters\SearchFilter;

class ElectronicBrandFilters
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
            new IsActiveFilter(
                $this->request->has('is_active') ? $this->request->boolean('is_active') : null
            ),
        ];
    }
}
