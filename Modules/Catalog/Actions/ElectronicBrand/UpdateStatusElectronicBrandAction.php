<?php

namespace Modules\Catalog\Actions\ElectronicBrand;

use Modules\Catalog\Contracts\Repositories\ElectronicBrandRepositoryInterface;
use Modules\Catalog\Models\ElectronicBrand;

class UpdateStatusElectronicBrandAction
{
    public function __construct(
        private readonly ElectronicBrandRepositoryInterface $repository,
    ) {}

    public function handle(ElectronicBrand $electronicBrand, bool $isActive): ElectronicBrand
    {
        return $this->repository->updateStatus($electronicBrand, $isActive);
    }
}
