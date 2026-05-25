<?php

namespace Modules\Classifieds\QueryFilters\Filters;

use Illuminate\Database\Eloquent\Builder;

final class StudyTypeFilter
{
    public function __construct(
        private readonly ?string $value,
    ) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->value) {
            return $query;
        }

        return $query->where('study_type', $this->value);
    }
}
