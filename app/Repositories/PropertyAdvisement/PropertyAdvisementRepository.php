<?php

namespace App\Repositories\PropertyAdvisement;

use App\Contracts\Repositories\PropertyAdvisement\PropertyAdvisementRepositoryInterface;
use App\Models\PropertyAdvisement;
use App\Models\User;
use App\QueryFilters\PropertyAdvisement\PropertyAdvisementFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PropertyAdvisementRepository implements PropertyAdvisementRepositoryInterface
{
  public function getUserAdvisements(User $user, PropertyAdvisementFilters $filters): LengthAwarePaginator
  {
    $query = $user->propertyAdvisements()->getQuery();

    $query = $filters->apply($query);

    return $query
      ->with([
        'propertyType.translation',
        'city.translation',
        'region.translation',
        'category.translation',
        'media',
      ])
      ->latest()
      ->paginate($filters->perPage());
  }

  public function getPublishedAdvisements(PropertyAdvisementFilters $filters): LengthAwarePaginator
  {
    $query = PropertyAdvisement::query()->published();

    $query = $filters->apply($query);

    return $query
      ->with([
        'propertyType.translation',
        'city.translation',
        'region.translation',
        'category.translation',
        'user',
        'media',
      ])
      ->latest()
      ->paginate($filters->perPage());
  }

  /**
   * @param  array<string, mixed>  $data
   */
  public function create(array $data): PropertyAdvisement
  {
    return PropertyAdvisement::query()->create($data);
  }

  /**
   * @param  array<string, mixed>  $data
   */
  public function update(PropertyAdvisement $model, array $data): PropertyAdvisement
  {
    $model->update($data);

    return $model;
  }
}
