<?php

namespace Modules\Geo\Actions\Nationality;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Geo\Contracts\Repositories\NationalityRepositoryInterface;

class ListNationalitiesForApiAction
{
    public function __construct(
        private readonly NationalityRepositoryInterface $repository,
    ) {}

    public function handle(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForApi($search, $perPage);
    }
}
