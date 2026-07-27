<?php

namespace Modules\Catalog\Actions\DeviceCategory;

use Modules\Catalog\Concerns\HandlesTransactionalFileUpload;
use Modules\Catalog\Contracts\Repositories\DeviceCategoryRepositoryInterface;
use Modules\Catalog\DTOs\UpdateDeviceCategoryDTO;
use Modules\Catalog\Models\DeviceCategory;
use Throwable;

class UpdateDeviceCategoryAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly DeviceCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(DeviceCategory $deviceCategory, UpdateDeviceCategoryDTO $dto): DeviceCategory
    {
        return $this->storeFileWithCleanup(
            file: $dto->icon,
            directory: 'device_categories',
            disk: null,
            previousPath: $dto->icon !== null ? $deviceCategory->icon : null,
            dbWork: function (?string $iconPath) use ($dto, $deviceCategory): DeviceCategory {
                $data = [
                    'parent_id' => $dto->parentId,
                ];

                if ($iconPath !== null) {
                    $data['icon'] = $iconPath;
                }

                $deviceCategory = $this->repository->update($deviceCategory, $data);
                $deviceCategory->translations()->delete();
                $deviceCategory->translations()->createMany($dto->translations);

                return $deviceCategory->load(['translation']);
            },
        );
    }
}
