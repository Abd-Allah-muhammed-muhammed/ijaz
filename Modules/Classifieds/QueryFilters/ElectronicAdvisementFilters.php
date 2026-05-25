<?php

namespace Modules\Classifieds\QueryFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Classifieds\QueryFilters\Filters\CityFilter;
use Modules\Classifieds\QueryFilters\Filters\ConditionFilter;
use Modules\Classifieds\QueryFilters\Filters\DeviceCategoryFilter;
use Modules\Classifieds\QueryFilters\Filters\PriceRangeFilter;
use Modules\Classifieds\QueryFilters\Filters\RegionFilter;
use Modules\Classifieds\QueryFilters\Filters\SearchFilter;
use Modules\Classifieds\QueryFilters\Filters\StatusFilter;

final class ElectronicAdvisementFilters
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
     * @return array<object>
     */
    private function filters(): array
    {
        $filters = [
            new ConditionFilter($this->request->filled('condition') ? (string) $this->request->string('condition') : null),
            new DeviceCategoryFilter($this->request->filled('device_category_id') ? $this->request->integer('device_category_id') : null),
            new CityFilter($this->request->filled('city_id') ? $this->request->integer('city_id') : null),
            new RegionFilter($this->request->filled('region_id') ? $this->request->integer('region_id') : null),
            new PriceRangeFilter(
                $this->request->filled('min_price') ? $this->request->float('min_price') : null,
                $this->request->filled('max_price') ? $this->request->float('max_price') : null,
            ),
            new SearchFilter($this->request->filled('search') ? (string) $this->request->string('search') : null),
        ];

        if ($this->includeStatus) {
            $filters[] = new StatusFilter($this->request->filled('status') ? (string) $this->request->string('status') : null);
        }

        return $filters;
    }
}
