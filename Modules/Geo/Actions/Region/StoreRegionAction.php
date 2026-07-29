<?php

namespace Modules\Geo\Actions\Region;

use Illuminate\Support\Facades\DB;
use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;
use Modules\Geo\DTOs\StoreRegionDTO;
use Modules\Geo\Models\Region;
use Throwable;

class StoreRegionAction
{
    public function __construct(
        private readonly RegionRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreRegionDTO $dto): Region
    {
        return DB::transaction(fn (): Region => $this->repository->create($dto->translations));
    }
}
