<?php

namespace Modules\Catalog\Actions\ElectronicBrand;

use App\Support\LookupCache;
use Modules\Catalog\Contracts\Repositories\ElectronicBrandRepositoryInterface;
use Modules\Catalog\Models\ElectronicBrand;

class UpdateStatusElectronicBrandAction
{
    public function __construct(
        private readonly ElectronicBrandRepositoryInterface $repository,
    ) {}

    public function handle(ElectronicBrand $electronicBrand, bool $isActive): ElectronicBrand
    {
        $electronicBrand = $this->repository->updateStatus($electronicBrand, $isActive);

        LookupCache::forgetAllLocales('electronic-brands:all');

        return $electronicBrand;
    }
}
