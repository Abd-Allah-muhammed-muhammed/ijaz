<?php

namespace Modules\Catalog\Actions\DeviceCategory;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\DeviceCategoryRepositoryInterface;
use Modules\Catalog\DTOs\UpdateDeviceCategoryDTO;
use Modules\Catalog\Models\DeviceCategory;
use Throwable;

class UpdateDeviceCategoryAction
{
    public function __construct(
        private readonly DeviceCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(DeviceCategory $deviceCategory, UpdateDeviceCategoryDTO $dto): DeviceCategory
    {
        DB::beginTransaction();
        try {
            $data = [
                'parent_id' => $dto->parentId,
            ];

            if ($dto->icon) {
                $deviceCategory->deleteIcon();
                $data['icon'] = $dto->icon;
            }

            $deviceCategory = $this->repository->update($deviceCategory, $data);
            $deviceCategory->translations()->delete();
            $deviceCategory->translations()->createMany($dto->translations);

            DB::commit();

            return $deviceCategory->load(['translation']);
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }
}
