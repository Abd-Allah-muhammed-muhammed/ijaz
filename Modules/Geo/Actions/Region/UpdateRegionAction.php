<?php

namespace Modules\Geo\Actions\Region;

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
        return DB::transaction(
            fn (): Region => $this->repository->update($region, $dto->translations)
        );
    }
}
