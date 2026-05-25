<?php

namespace Modules\Catalog\QueryFilters\PropertyCategory\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;
use Modules\Catalog\Services\Normalize\Normalize;

class SearchFilter implements QueryFilterInterface
{
    public function __construct(private readonly ?string $search) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->search) {
            return $query;
        }

        $search = Normalize::make($this->search, app()->getLocale());

        return $query->whereTranslationLike('normalized_title', "%{$search}%");
    }
}
