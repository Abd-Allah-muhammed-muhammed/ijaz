<?php

namespace Modules\Classifieds\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\QueryFilters\InstituteAdvisementFilters;

interface InstituteAdvisementRepositoryInterface
{
    public function getUserAdvisements(User $user, InstituteAdvisementFilters $filters): LengthAwarePaginator;

    public function getPublishedAdvisements(InstituteAdvisementFilters $filters): LengthAwarePaginator;

    public function getPublishedAdvisementsForUser(User $user, InstituteAdvisementFilters $filters): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): InstituteAdvisement;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(InstituteAdvisement $model, array $data): InstituteAdvisement;

    public function paginateForDashboard(Request $request): LengthAwarePaginator;
}
