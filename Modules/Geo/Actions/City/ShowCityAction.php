<?php

namespace Modules\Geo\Actions\City;

use Modules\Geo\Contracts\Repositories\CityRepositoryInterface;
use Modules\Geo\Models\City;

class ShowCityAction
{
    public function __construct(
        private readonly CityRepositoryInterface $repository,
    ) {}

    public function handle(City $city): City
    {
        return $this->repository->loadForEdit($city);
    }
}
