<?php

namespace Modules\Catalog\Actions\ElectronicBrand;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\ElectronicBrandRepositoryInterface;

class ListElectronicBrandsAction
{
    public function __construct(
        private readonly ElectronicBrandRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
