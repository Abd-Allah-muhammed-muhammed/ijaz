<?php

namespace Modules\Geo\Actions\City;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Geo\Contracts\Repositories\CityRepositoryInterface;
use Modules\Geo\Models\Region;

class ListCitiesForApiAction
{
    public function __construct(
        private readonly CityRepositoryInterface $repository,
    ) {}

    public function handle(Region $region, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->repository->paginateForApiByRegion($region, $search, $perPage);
    }
}
