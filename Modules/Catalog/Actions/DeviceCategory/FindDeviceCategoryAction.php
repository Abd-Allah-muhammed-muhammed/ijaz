<?php

namespace Modules\Catalog\Actions\DeviceCategory;

use Modules\Catalog\Contracts\Repositories\DeviceCategoryRepositoryInterface;
use Modules\Catalog\Models\DeviceCategory;

class FindDeviceCategoryAction
{
    public function __construct(
        private readonly DeviceCategoryRepositoryInterface $repository,
    ) {}

    public function handle(int $id): ?DeviceCategory
    {
        return $this->repository->find($id);
    }
}
