<?php

namespace Modules\Classifieds\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Classifieds\QueryFilters\PropertyAdvisementFilters;

interface PropertyAdvisementRepositoryInterface
{
    public function getUserAdvisements(User $user, PropertyAdvisementFilters $filters): LengthAwarePaginator;

    public function getPublishedAdvisements(PropertyAdvisementFilters $filters): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PropertyAdvisement;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PropertyAdvisement $model, array $data): PropertyAdvisement;
}
