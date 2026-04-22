<?php

namespace App\Contracts\Repositories\CarAdvisement;

use App\Models\CarAdvisement;
use App\Models\User;
use App\QueryFilters\CarAdvisement\CarAdvisementFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
