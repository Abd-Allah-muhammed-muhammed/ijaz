<?php

namespace App\Contracts\Repositories\PropertyAdvisement;

use App\Models\PropertyAdvisement;
use App\Models\User;
use App\QueryFilters\PropertyAdvisement\PropertyAdvisementFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PropertyAdvisementRepositoryInterface
{
  public function getUserAdvisements(User $user, PropertyAdvisementFilters $filters): LengthAwarePaginator;

  public function getPublishedAdvisements(PropertyAdvisementFilters $filters): LengthAwarePaginator;

  /**
   * @param  array<string, mixed>  $data
   */
  public function create(array $data): PropertyAdvisement;

  /**
   * @param  array<string, mixed>  $data
   */
  public function update(PropertyAdvisement $model, array $data): PropertyAdvisement;
}
