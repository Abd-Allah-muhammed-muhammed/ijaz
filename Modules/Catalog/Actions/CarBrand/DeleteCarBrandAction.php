<?php

namespace Modules\Catalog\Actions\CarBrand;

use App\Support\LookupCache;
use Modules\Catalog\Contracts\Repositories\CarBrandRepositoryInterface;
use Modules\Catalog\Models\CarBrand;

class DeleteCarBrandAction
{
    public function __construct(
        private readonly CarBrandRepositoryInterface $repository,
    ) {}

    public function handle(CarBrand $carBrand): void
    {
        $brandId = (int) $carBrand->id;

        $this->repository->delete($carBrand);

        LookupCache::forgetAllLocales('car-brands:all');
        LookupCache::forgetScopedAllLocales('car-types:by-brand', $brandId);
        LookupCache::forgetScopedAllLocales('car-types:by-brand', 0);
    }
}
