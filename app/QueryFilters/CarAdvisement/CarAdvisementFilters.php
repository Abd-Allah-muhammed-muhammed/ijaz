<?php

namespace App\QueryFilters\CarAdvisement;

use App\QueryFilters\CarAdvisement\Filters\CarBrandFilter;
use App\QueryFilters\CarAdvisement\Filters\CarCategoryFilter;
use App\QueryFilters\CarAdvisement\Filters\CarTypeFilter;
use App\QueryFilters\CarAdvisement\Filters\CityFilter;
use App\QueryFilters\CarAdvisement\Filters\OperationFilter;
use App\QueryFilters\CarAdvisement\Filters\PriceRangeFilter;
use App\QueryFilters\CarAdvisement\Filters\RegionFilter;
use App\QueryFilters\CarAdvisement\Filters\SearchFilter;
use App\QueryFilters\CarAdvisement\Filters\UsageStatusFilter;
use App\QueryFilters\CarAdvisement\Filters\YearRangeFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class CarAdvisementFilters
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
        return [
            new OperationFilter($this->request->string('operation')),
            new UsageStatusFilter($this->request->string('usage_status')),
            new CarBrandFilter($this->request->integer('car_brand_id')),
            new CarTypeFilter($this->request->integer('car_type_id')),
            new CarCategoryFilter($this->request->integer('car_category_id')),
            new CityFilter($this->request->integer('city_id')),
            new RegionFilter($this->request->integer('region_id')),
            new YearRangeFilter(
                $this->request->integer('min_year'),
                $this->request->integer('max_year'),
            ),
            new PriceRangeFilter(
                $this->request->float('min_price'),
                $this->request->float('max_price'),
            ),
            new SearchFilter($this->request->string('search')),
        ];
    }
}
