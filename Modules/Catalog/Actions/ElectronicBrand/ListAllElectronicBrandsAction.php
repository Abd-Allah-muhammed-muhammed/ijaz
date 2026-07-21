<?php

namespace Modules\Catalog\Actions\ElectronicBrand;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\ElectronicBrandRepositoryInterface;
use Modules\Catalog\Models\ElectronicBrand;

class ListAllElectronicBrandsAction
{
    public function __construct(
        private readonly ElectronicBrandRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, ElectronicBrand>
     */
    public function handle(Request $request): Collection
    {
        return $this->repository->getAll($request);
    }
}
