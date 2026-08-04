<?php

namespace Modules\Geo\Actions\Region;

use App\Support\LookupCache;
use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;
use Modules\Geo\Models\Region;

class DeleteRegionAction
{
    public function __construct(
        private readonly RegionRepositoryInterface $repository,
    ) {}

    public function handle(Region $region): void
    {
        $regionId = $region->id;

        $this->repository->delete($region);

        LookupCache::forgetAllLocales('regions:all');
        LookupCache::forgetAllLocales('regions:dropdown');
        LookupCache::forgetScopedAllLocales('cities:by-region', $regionId);
        LookupCache::forgetScopedAllLocales('cities:by-region', 0);
    }
}
