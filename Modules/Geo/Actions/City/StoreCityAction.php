<?php

namespace Modules\Geo\Actions\City;

use Illuminate\Support\Facades\DB;
use Modules\Geo\Contracts\Repositories\CityRepositoryInterface;
use Modules\Geo\DTOs\StoreCityDTO;
use Modules\Geo\Models\City;
use Throwable;

class StoreCityAction
{
    public function __construct(
        private readonly CityRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreCityDTO $dto): City
    {
        return DB::transaction(
            fn (): City => $this->repository->create($dto->regionId, $dto->translations)
        );
    }
}
