<?php

namespace App\Support;

use App\Support\Normalize as TextNormalize;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Canonical Arabic-aware translation search for Astrotomic models.
 *
 * Always search normalized_* columns with a Normalize::make()'d term —
 * Dashboard, API, and select endpoints must use this same path.
 */
final class TranslationSearch
{
    /**
     * Normalize a user search term for the current (or given) locale.
     */
    public static function term(?string $search, ?string $locale = null): ?string
    {
        if ($search === null || $search === '') {
            return null;
        }

        $normalized = TextNormalize::make($search, $locale ?? app()->getLocale())->toString();

        return $normalized === '' ? null : $normalized;
    }

    /**
     * Apply whereTranslationLike against a normalized_* translation column.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    public static function apply(Builder $query, ?string $search, string $column = 'normalized_title'): Builder
    {
        $term = self::term($search);

        if ($term === null) {
            return $query;
        }

        return $query->whereTranslationLike($column, "%{$term}%");
    }
}
