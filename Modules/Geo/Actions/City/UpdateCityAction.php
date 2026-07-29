<?php

namespace Modules\Geo\Actions\City;

use Illuminate\Support\Facades\DB;
use Modules\Geo\Contracts\Repositories\CityRepositoryInterface;
use Modules\Geo\DTOs\UpdateCityDTO;
use Modules\Geo\Models\City;
use Throwable;

class UpdateCityAction
{
    public function __construct(
        private readonly CityRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(City $city, UpdateCityDTO $dto): City
    {
        return DB::transaction(
            fn (): City => $this->repository->update($city, $dto->regionId, $dto->translations)
        );
    }
}
