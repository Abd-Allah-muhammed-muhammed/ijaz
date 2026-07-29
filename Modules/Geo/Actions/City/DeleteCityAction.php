<?php

namespace Modules\Geo\Actions\City;

use Illuminate\Support\Facades\DB;
use Modules\Geo\Contracts\Repositories\CityRepositoryInterface;
use Modules\Geo\Models\City;
use Throwable;

class DeleteCityAction
{
    public function __construct(
        private readonly CityRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(City $city): void
    {
        DB::transaction(function () use ($city): void {
            $this->repository->delete($city);
        });
    }
}
