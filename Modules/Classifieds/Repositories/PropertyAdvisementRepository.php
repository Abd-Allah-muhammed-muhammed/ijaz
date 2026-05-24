<?php

namespace Modules\Classifieds\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Classifieds\Contracts\Repositories\PropertyAdvisementRepositoryInterface;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Classifieds\QueryFilters\PropertyAdvisementFilters;

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
