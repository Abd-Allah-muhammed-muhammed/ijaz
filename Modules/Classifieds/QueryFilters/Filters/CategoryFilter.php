<?php

namespace Modules\Classifieds\QueryFilters\Filters;

use App\Contracts\QueryFilters\QueryFilterInterface;
use Illuminate\Database\Eloquent\Builder;

class CategoryFilter implements QueryFilterInterface
{
    public function __construct(private readonly ?int $categoryId) {}

    public function apply(Builder $query): Builder
    {
        if (! $this->categoryId) {
            return $query;
        }

        return $query->where('category_id', $this->categoryId);
    }
}
