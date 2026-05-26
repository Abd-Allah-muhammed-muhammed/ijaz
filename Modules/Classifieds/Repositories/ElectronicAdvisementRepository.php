<?php

namespace Modules\Classifieds\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Classifieds\Contracts\Repositories\ElectronicAdvisementRepositoryInterface;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\QueryFilters\ElectronicAdvisementFilters;

final class ElectronicAdvisementRepository implements ElectronicAdvisementRepositoryInterface
{
    public function getUserAdvisements(User $user, ElectronicAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = $user->electronicAdvisements()->getQuery();
        $query = $filters->apply($query);

        return $query
            ->with([
                'deviceCategory',
                'electronicBrand',
                'city',
                'region',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function getPublishedAdvisements(ElectronicAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = ElectronicAdvisement::query()->published();
        $query = $filters->apply($query);

        return $query
            ->with([
                'deviceCategory',
                'electronicBrand',
                'city',
                'region',
                'user',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function create(array $data): ElectronicAdvisement
    {
        return ElectronicAdvisement::query()->create($data);
    }

    public function update(ElectronicAdvisement $model, array $data): ElectronicAdvisement
    {
        $model->update($data);

        return $model;
    }
}
