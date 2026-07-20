<?php

namespace Modules\Geo\Actions\Region;

use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;
use Modules\Geo\Models\Region;

class ShowRegionAction
{
    public function __construct(
        private readonly RegionRepositoryInterface $repository,
    ) {}

    public function handle(Region $region): Region
    {
        return $this->repository->loadForEdit($region);
    }
}
