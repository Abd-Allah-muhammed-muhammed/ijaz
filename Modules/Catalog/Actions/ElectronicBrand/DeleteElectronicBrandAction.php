<?php

namespace Modules\Catalog\Actions\ElectronicBrand;

use Modules\Catalog\Contracts\Repositories\ElectronicBrandRepositoryInterface;
use Modules\Catalog\Models\ElectronicBrand;

class DeleteElectronicBrandAction
{
    public function __construct(
        private readonly ElectronicBrandRepositoryInterface $repository,
    ) {}

    public function handle(ElectronicBrand $electronicBrand): void
    {
        $this->repository->delete($electronicBrand);
    }
}
