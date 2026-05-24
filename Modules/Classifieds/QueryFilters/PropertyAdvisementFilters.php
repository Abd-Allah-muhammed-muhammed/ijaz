<?php

namespace Modules\Classifieds\QueryFilters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Classifieds\QueryFilters\Filters\AreaFilter;
use Modules\Classifieds\QueryFilters\Filters\BedroomsFilter;
use Modules\Classifieds\QueryFilters\Filters\CategoryFilter;
use Modules\Classifieds\QueryFilters\Filters\CityFilter;
use Modules\Classifieds\QueryFilters\Filters\OperationFilter;
use Modules\Classifieds\QueryFilters\Filters\PriceFilter;
use Modules\Classifieds\QueryFilters\Filters\PropertyTypeFilter;
use Modules\Classifieds\QueryFilters\Filters\RegionFilter;
use Modules\Classifieds\QueryFilters\Filters\SearchFilter;
use Modules\Classifieds\QueryFilters\Filters\StatusFilter;

class PropertyAdvisementFilters
{
    public function __construct(
        private readonly Request $request,
        private readonly bool $includeStatus = false,
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
        return $this->request->integer('per_page', 15);
    }

    /**
     * @return array<int, QueryFilterInterface>
     */
    private function filters(): array
    {
        $filters = [
            new OperationFilter($this->request->filled('operation') ? (string) $this->request->string('operation') : null),
            new PropertyTypeFilter($this->request->filled('property_type_id') ? $this->request->integer('property_type_id') : null),
            new CityFilter($this->request->filled('city_id') ? $this->request->integer('city_id') : null),
            new RegionFilter($this->request->filled('region_id') ? $this->request->integer('region_id') : null),
            new CategoryFilter($this->request->filled('category_id') ? $this->request->integer('category_id') : null),
            new PriceFilter(
                $this->request->filled('min_price') ? $this->request->float('min_price') : null,
                $this->request->filled('max_price') ? $this->request->float('max_price') : null,
            ),
            new AreaFilter(
                $this->request->filled('min_area') ? $this->request->float('min_area') : null,
                $this->request->filled('max_area') ? $this->request->float('max_area') : null,
            ),
            new BedroomsFilter($this->request->filled('bedrooms_count') ? $this->request->integer('bedrooms_count') : null),
            new SearchFilter($this->request->filled('search') ? (string) $this->request->string('search') : null),
        ];

        if ($this->includeStatus) {
            array_unshift(
                $filters,
                new StatusFilter($this->request->filled('status') ? (string) $this->request->string('status') : null),
            );
        }

        return $filters;
    }
}
