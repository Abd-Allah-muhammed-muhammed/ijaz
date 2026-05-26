<?php

namespace Modules\Catalog\QueryFilters\ElectronicBrand\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;

class SearchFilter implements QueryFilterInterface
{
    public function __construct(private readonly ?string $search) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->search) {
            return $query;
        }

        return $query->whereTranslationLike('normalized_name', "%{$this->search}%");
    }
}
