<?php

namespace Modules\Catalog\Actions\ElectronicBrand;

use Modules\Catalog\Contracts\Repositories\ElectronicBrandRepositoryInterface;
use Modules\Catalog\Models\ElectronicBrand;

class FindElectronicBrandAction
{
    public function __construct(
        private readonly ElectronicBrandRepositoryInterface $repository,
    ) {}

    public function handle(int $id): ?ElectronicBrand
    {
        return $this->repository->find($id);
    }
}
