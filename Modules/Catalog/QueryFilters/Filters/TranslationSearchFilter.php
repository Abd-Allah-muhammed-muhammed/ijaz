<?php

namespace Modules\Catalog\QueryFilters\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use App\Support\TranslationSearch;
use Illuminate\Database\Eloquent\Builder;

/**
 * Query filter for Astrotomic translation search.
 *
 * Prefer normalize: true + a normalized_* column. Use normalize: false only for
 * domains that lack normalized_* columns (CarBrand, CarType, PropertyType).
 */
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

        if ($this->normalize) {
            return TranslationSearch::apply($query, $this->search, $this->column);
        }

        return $query->whereTranslationLike($this->column, "%{$this->search}%");
    }
}
