<?php

namespace Modules\Catalog\Actions\DeviceCategory;

use Modules\Catalog\Concerns\HandlesTransactionalFileUpload;
use Modules\Catalog\Contracts\Repositories\DeviceCategoryRepositoryInterface;
use Modules\Catalog\DTOs\StoreDeviceCategoryDTO;
use Modules\Catalog\Models\DeviceCategory;
use Throwable;

class StoreDeviceCategoryAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly DeviceCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreDeviceCategoryDTO $dto): DeviceCategory
    {
        return $this->storeFileWithCleanup(
            file: $dto->icon,
            directory: 'device_categories',
            disk: null,
            dbWork: function (?string $iconPath) use ($dto): DeviceCategory {
                $deviceCategory = $this->repository->create([
                    'parent_id' => $dto->parentId,
                    'icon' => $iconPath,
                ]);
                $deviceCategory->translations()->createMany($dto->translations);

                return $deviceCategory->load(['translation']);
            },
        );
    }
}
