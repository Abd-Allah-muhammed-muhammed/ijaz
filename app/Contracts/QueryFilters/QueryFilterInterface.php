<?php

namespace App\Contracts\QueryFilters;

use Illuminate\Database\Eloquent\Builder;

interface QueryFilterInterface
{
  public function apply(Builder $query): Builder;
}
