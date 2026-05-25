<?php

namespace Modules\Classifieds\QueryFilters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Classifieds\QueryFilters\Filters\CityFilter;
use Modules\Classifieds\QueryFilters\Filters\FeesRangeFilter;
use Modules\Classifieds\QueryFilters\Filters\InstituteTypeFilter;
use Modules\Classifieds\QueryFilters\Filters\RegionFilter;
use Modules\Classifieds\QueryFilters\Filters\SearchFilter;
use Modules\Classifieds\QueryFilters\Filters\SpecializationFilter;
use Modules\Classifieds\QueryFilters\Filters\StatusFilter;
use Modules\Classifieds\QueryFilters\Filters\StudyTypeFilter;

final class InstituteAdvisementFilters
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
            new InstituteTypeFilter($this->request->filled('type') ? (string) $this->request->string('type') : null),
            new StudyTypeFilter($this->request->filled('study_type') ? (string) $this->request->string('study_type') : null),
            new SpecializationFilter($this->request->filled('specialization_id') ? $this->request->integer('specialization_id') : null),
            new CityFilter($this->request->filled('city_id') ? $this->request->integer('city_id') : null),
            new RegionFilter($this->request->filled('region_id') ? $this->request->integer('region_id') : null),
            new FeesRangeFilter(
                $this->request->filled('min_fees') ? $this->request->float('min_fees') : null,
                $this->request->filled('max_fees') ? $this->request->float('max_fees') : null,
            ),
            new SearchFilter($this->request->filled('search') ? (string) $this->request->string('search') : null),
        ];

        if ($this->includeStatus) {
            $filters[] = new StatusFilter($this->request->filled('status') ? (string) $this->request->string('status') : null);
        }

        return $filters;
    }
}
