<?php

namespace Modules\Classifieds\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Classifieds\Contracts\Repositories\CarAdvisementRepositoryInterface;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\QueryFilters\CarAdvisementFilters;

final class CarAdvisementRepository implements CarAdvisementRepositoryInterface
{
    public function getUserAdvisements(User $user, CarAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = $user->carAdvisements()->getQuery();
        $query = $filters->apply($query);

        return $query
            ->with([
                'carBrand',
                'carType',
                'carCategory',
                'city',
                'region',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function getPublishedAdvisements(CarAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = CarAdvisement::query()->published();
        $query = $filters->apply($query);

        return $query
            ->with([
                'carBrand',
                'carType',
                'carCategory',
                'city',
                'region',
                'user',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function create(array $data): CarAdvisement
    {
        return CarAdvisement::query()->create($data);
    }

    public function update(CarAdvisement $model, array $data): CarAdvisement
    {
        $model->update($data);

        return $model;
    }
}
