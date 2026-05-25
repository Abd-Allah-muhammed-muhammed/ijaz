<?php

namespace Modules\Classifieds\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\QueryFilters\InstituteAdvisementFilters;

interface InstituteAdvisementRepositoryInterface
{
    public function getUserAdvisements(User $user, InstituteAdvisementFilters $filters): LengthAwarePaginator;

    public function getPublishedAdvisements(InstituteAdvisementFilters $filters): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): InstituteAdvisement;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(InstituteAdvisement $model, array $data): InstituteAdvisement;
}
