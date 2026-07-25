<?php

namespace Modules\Geo\Actions\Region;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;

class ListRegionsForApiAction
{
    public function __construct(
        private readonly RegionRepositoryInterface $repository,
    ) {}

    public function handle(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForApi($search, $perPage);
    }
}
