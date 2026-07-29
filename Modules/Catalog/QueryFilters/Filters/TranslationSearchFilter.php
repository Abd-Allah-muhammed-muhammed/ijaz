<?php

namespace Modules\Catalog\QueryFilters\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use App\Support\Normalize;
use Illuminate\Database\Eloquent\Builder;

final class TranslationSearchFilter implements QueryFilterInterface
{
    public function __construct(
        private readonly ?string $search,
        private readonly string $column,
        private readonly bool $normalize = true,
    ) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->search) {
            return $query;
        }

        $term = $this->normalize
            ? Normalize::make($this->search, app()->getLocale())
            : $this->search;

        return $query->whereTranslationLike($this->column, "%{$term}%");
    }
}
