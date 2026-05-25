<?php

namespace Modules\Classifieds\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Classifieds\Contracts\Repositories\InstituteAdvisementRepositoryInterface;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\QueryFilters\InstituteAdvisementFilters;

final class InstituteAdvisementRepository implements InstituteAdvisementRepositoryInterface
{
    public function getUserAdvisements(User $user, InstituteAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = $user->instituteAdvisements()->getQuery();
        $query = $filters->apply($query);

        return $query
            ->with([
                'specialization',
                'city',
                'region',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function getPublishedAdvisements(InstituteAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = InstituteAdvisement::query()->published();
        $query = $filters->apply($query);

        return $query
            ->with([
                'specialization',
                'city',
                'region',
                'user',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function create(array $data): InstituteAdvisement
    {
        return InstituteAdvisement::query()->create($data);
    }

    public function update(InstituteAdvisement $model, array $data): InstituteAdvisement
    {
        $model->update($data);

        return $model;
    }
}
