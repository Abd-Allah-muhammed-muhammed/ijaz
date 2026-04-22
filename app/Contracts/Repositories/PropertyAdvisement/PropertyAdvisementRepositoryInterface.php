<?php

namespace App\Contracts\Repositories\PropertyAdvisement;

use App\DTOs\PropertyAdvisement\PropertyAdvisementFiltersDTO;
use App\Models\PropertyAdvisement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PropertyAdvisementRepositoryInterface
{
  public function getUserAdvisements(User $user, PropertyAdvisementFiltersDTO $filters): LengthAwarePaginator;

  public function getPublishedAdvisements(PropertyAdvisementFiltersDTO $filters): LengthAwarePaginator;

  /**
   * @param  array<string, mixed>  $data
   */
  public function create(array $data): PropertyAdvisement;

  /**
   * @param  array<string, mixed>  $data
   */
  public function update(PropertyAdvisement $model, array $data): PropertyAdvisement;
}
