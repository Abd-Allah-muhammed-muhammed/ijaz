<?php

namespace Modules\Catalog\Actions\DeviceCategory;

use Modules\Catalog\Contracts\Repositories\DeviceCategoryRepositoryInterface;
use Modules\Catalog\Models\DeviceCategory;

class DeleteDeviceCategoryAction
{
    public function __construct(
        private readonly DeviceCategoryRepositoryInterface $repository,
    ) {}

    public function handle(DeviceCategory $deviceCategory): void
    {
        $this->repository->delete($deviceCategory);
    }
}
