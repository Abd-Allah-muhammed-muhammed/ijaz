<?php

namespace Modules\Geo\Actions\Region;

use Illuminate\Database\Eloquent\Collection;
use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;
use Modules\Geo\Models\Region;

class ListRegionsForSelectAction
{
    public function __construct(
        private readonly RegionRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Region>
     */
    public function handle(?string $search = null): Collection
    {
        return $this->repository->listForSelect($search);
    }
}
