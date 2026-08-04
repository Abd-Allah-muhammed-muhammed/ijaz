<?php

namespace Modules\Geo\Actions\Region;

use App\Support\LookupCache;
use Illuminate\Support\Facades\DB;
use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;
use Modules\Geo\DTOs\UpdateRegionDTO;
use Modules\Geo\Models\Region;
use Throwable;

class UpdateRegionAction
{
    public function __construct(
        private readonly RegionRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Region $region, UpdateRegionDTO $dto): Region
    {
        $region = DB::transaction(
            fn (): Region => $this->repository->update($region, $dto->translations)
        );

        LookupCache::forgetAllLocales('regions:all');
        LookupCache::forgetAllLocales('regions:dropdown');

        return $region;
    }
}
