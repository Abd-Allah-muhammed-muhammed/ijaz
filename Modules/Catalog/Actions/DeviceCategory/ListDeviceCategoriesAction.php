<?php

namespace Modules\Catalog\Actions\DeviceCategory;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\DeviceCategoryRepositoryInterface;

class ListDeviceCategoriesAction
{
    public function __construct(
        private readonly DeviceCategoryRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
