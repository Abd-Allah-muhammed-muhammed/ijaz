<?php

namespace Modules\Geo\Actions\City;

use App\Support\LookupCache;
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
        $regionId = (int) $city->region_id;

        DB::transaction(function () use ($city): void {
            $this->repository->delete($city);
        });

        LookupCache::forgetScopedAllLocales('cities:by-region', $regionId);
        LookupCache::forgetScopedAllLocales('cities:by-region', 0);
    }
}
