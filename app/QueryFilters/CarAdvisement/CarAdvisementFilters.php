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
      new OperationFilter($this->request->filled('operation') ? (string) $this->request->string('operation') : null),
      new UsageStatusFilter($this->request->filled('usage_status') ? (string) $this->request->string('usage_status') : null),
      new CarBrandFilter($this->request->filled('car_brand_id') ? $this->request->integer('car_brand_id') : null),
      new CarTypeFilter($this->request->filled('car_type_id') ? $this->request->integer('car_type_id') : null),
      new CarCategoryFilter($this->request->filled('car_category_id') ? $this->request->integer('car_category_id') : null),
      new CityFilter($this->request->filled('city_id') ? $this->request->integer('city_id') : null),
      new RegionFilter($this->request->filled('region_id') ? $this->request->integer('region_id') : null),
      new YearRangeFilter(
        $this->request->filled('min_year') ? $this->request->integer('min_year') : null,
        $this->request->filled('max_year') ? $this->request->integer('max_year') : null,
      ),
      new PriceRangeFilter(
        $this->request->filled('min_price') ? $this->request->float('min_price') : null,
        $this->request->filled('max_price') ? $this->request->float('max_price') : null,
      ),
      new SearchFilter($this->request->filled('search') ? (string) $this->request->string('search') : null),
    ];
  }
}