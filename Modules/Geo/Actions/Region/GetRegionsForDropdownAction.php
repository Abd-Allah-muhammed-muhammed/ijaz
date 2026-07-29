<?php

namespace Modules\Geo\Actions\Region;

use Illuminate\Database\Eloquent\Collection;
use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;
use Modules\Geo\Models\Region;

class GetRegionsForDropdownAction
{
    public function __construct(
        private readonly RegionRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Region>
     */
    public function handle(): Collection
    {
        return $this->repository->getAllForDropdown();
    }
}
