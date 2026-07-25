<?php

namespace Modules\Geo\Actions\City;

use Illuminate\Database\Eloquent\Collection;
use Modules\Geo\Contracts\Repositories\CityRepositoryInterface;
use Modules\Geo\Models\City;

class ListCitiesForSelectAction
{
    public function __construct(
        private readonly CityRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, City>
     */
    public function handle(?string $search = null, int $regionId = 0): Collection
    {
        return $this->repository->listForSelect($search, $regionId);
    }
}
