<?php

namespace Modules\Classifieds\QueryFilters\Filters;

use App\Support\TranslationSearch;
use Illuminate\Database\Eloquent\Builder;
use Stringable;

final class SearchFilter
{
    public function __construct(
        private readonly string|Stringable|null $value,
    ) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->value) {
            return $query;
        }

        $search = (string) $this->value;
        $term = TranslationSearch::term($search) ?? $search;

        return $query->where(function (Builder $q) use ($term): void {
            $q->where('normalized_title', 'like', "%{$term}%")
                ->orWhere('normalized_description', 'like', "%{$term}%");
        });
    }
}
