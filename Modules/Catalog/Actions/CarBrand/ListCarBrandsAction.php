<?php

namespace Modules\Catalog\Actions\CarBrand;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\CarBrandRepositoryInterface;

class ListCarBrandsAction
{
    public function __construct(
        private readonly CarBrandRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
