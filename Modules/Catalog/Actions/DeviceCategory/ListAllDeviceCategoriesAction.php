<?php

namespace Modules\Catalog\Actions\DeviceCategory;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\DeviceCategoryRepositoryInterface;
use Modules\Catalog\Models\DeviceCategory;

class ListAllDeviceCategoriesAction
{
    public function __construct(
        private readonly DeviceCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, DeviceCategory>
     */
    public function handle(Request $request): Collection
    {
        return $this->repository->getAll($request);
    }
}
