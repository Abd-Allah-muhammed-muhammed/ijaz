<?php

namespace Modules\Classifieds\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\QueryFilters\ElectronicAdvisementFilters;

interface ElectronicAdvisementRepositoryInterface
{
    public function getUserAdvisements(User $user, ElectronicAdvisementFilters $filters): LengthAwarePaginator;

    public function getPublishedAdvisements(ElectronicAdvisementFilters $filters): LengthAwarePaginator;

    public function getPublishedAdvisementsForUser(User $user, ElectronicAdvisementFilters $filters): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ElectronicAdvisement;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ElectronicAdvisement $model, array $data): ElectronicAdvisement;

    public function paginateForDashboard(Request $request): LengthAwarePaginator;
}
