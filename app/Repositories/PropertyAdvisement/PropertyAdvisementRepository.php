<?php

namespace App\Repositories\PropertyAdvisement;

use App\Contracts\Repositories\PropertyAdvisement\PropertyAdvisementRepositoryInterface;
use App\DTOs\PropertyAdvisement\PropertyAdvisementFiltersDTO;
use App\Models\PropertyAdvisement;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PropertyAdvisementRepository implements PropertyAdvisementRepositoryInterface
{
  public function getUserAdvisements(User $user, PropertyAdvisementFiltersDTO $filters): LengthAwarePaginator
  {
    $query = $user->propertyAdvisements()->getQuery();

    $this->applyFilters($query, $filters, true);

    return $query
      ->with([
        'propertyType.translation',
        'city.translation',
        'region.translation',
        'category.translation',
        'media',
      ])
      ->latest()
      ->paginate($filters->perPage);
  }

  public function getPublishedAdvisements(PropertyAdvisementFiltersDTO $filters): LengthAwarePaginator
  {
    $query = PropertyAdvisement::query()->published();

    $this->applyFilters($query, $filters);

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
      ->paginate($filters->perPage);
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

  private function applyFilters(Builder $query, PropertyAdvisementFiltersDTO $filters, bool $includeStatus = false): void
  {
    if ($includeStatus && $filters->status !== null) {
      $query->where('status', $filters->status);
    }

    if ($filters->operation !== null) {
      $query->where('operation', $filters->operation);
    }

    if ($filters->propertyTypeId !== null) {
      $query->where('property_type_id', $filters->propertyTypeId);
    }

    if ($filters->cityId !== null) {
      $query->where('city_id', $filters->cityId);
    }

    if ($filters->regionId !== null) {
      $query->where('region_id', $filters->regionId);
    }

    if ($filters->categoryId !== null) {
      $query->where('category_id', $filters->categoryId);
    }

    if ($filters->minPrice !== null) {
      $query->where('price', '>=', $filters->minPrice);
    }

    if ($filters->maxPrice !== null) {
      $query->where('price', '<=', $filters->maxPrice);
    }

    if ($filters->minArea !== null) {
      $query->where('area', '>=', $filters->minArea);
    }

    if ($filters->maxArea !== null) {
      $query->where('area', '<=', $filters->maxArea);
    }

    if ($filters->bedroomsCount !== null) {
      $query->where('bedrooms_count', $filters->bedroomsCount);
    }

    if ($filters->search !== null) {
      $query->where(function (Builder $nestedQuery) use ($filters) {
        $nestedQuery->where('title', 'like', "%{$filters->search}%")
          ->orWhere('description', 'like', "%{$filters->search}%");
      });
    }
  }
}
