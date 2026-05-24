<?php

namespace Modules\Classifieds\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\QueryFilters\CarAdvisementFilters;

interface CarAdvisementRepositoryInterface
{
    public function getUserAdvisements(User $user, CarAdvisementFilters $filters): LengthAwarePaginator;

    public function getPublishedAdvisements(CarAdvisementFilters $filters): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): CarAdvisement;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(CarAdvisement $model, array $data): CarAdvisement;
}
