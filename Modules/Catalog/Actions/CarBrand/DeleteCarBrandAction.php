<?php

namespace Modules\Catalog\Actions\CarBrand;

use Modules\Catalog\Contracts\Repositories\CarBrandRepositoryInterface;
use Modules\Catalog\Models\CarBrand;

class DeleteCarBrandAction
{
    public function __construct(
        private readonly CarBrandRepositoryInterface $repository,
    ) {}

    public function handle(CarBrand $carBrand): void
    {
        $this->repository->delete($carBrand);
    }
}
