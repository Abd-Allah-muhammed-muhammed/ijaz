<?php

namespace Modules\Catalog\Actions\DeviceCategory;

use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Contracts\Repositories\DeviceCategoryRepositoryInterface;
use Modules\Catalog\Models\DeviceCategory;

class ListRootDeviceCategoriesAction
{
    public function __construct(
        private readonly DeviceCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, DeviceCategory>
     */
    public function handle(?int $excludeId = null): Collection
    {
        return $this->repository->getRootCategories($excludeId);
    }
}
