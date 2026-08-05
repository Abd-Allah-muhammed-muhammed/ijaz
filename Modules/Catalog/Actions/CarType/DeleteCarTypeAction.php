<?php

namespace Modules\Catalog\Actions\CarType;

use App\Support\LookupCache;
use Modules\Catalog\Contracts\Repositories\CarTypeRepositoryInterface;
use Modules\Catalog\Models\CarType;

class DeleteCarTypeAction
{
    public function __construct(
        private readonly CarTypeRepositoryInterface $repository,
    ) {}

    public function handle(CarType $carType): void
    {
        $brandId = (int) $carType->car_brand_id;

        $this->repository->delete($carType);

        LookupCache::forgetScopedAllLocales('car-types:by-brand', $brandId);
        LookupCache::forgetScopedAllLocales('car-types:by-brand', 0);
    }
}
