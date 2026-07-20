<?php

namespace Modules\Geo\Actions\Region;

use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;
use Modules\Geo\Models\Region;

class DeleteRegionAction
{
    public function __construct(
        private readonly RegionRepositoryInterface $repository,
    ) {}

    public function handle(Region $region): void
    {
        $this->repository->delete($region);
    }
}
