<?php

namespace Modules\Catalog\Actions\DeviceCategory;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\DeviceCategoryRepositoryInterface;
use Modules\Catalog\DTOs\StoreDeviceCategoryDTO;
use Modules\Catalog\Models\DeviceCategory;
use Throwable;

class StoreDeviceCategoryAction
{
    public function __construct(
        private readonly DeviceCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreDeviceCategoryDTO $dto): DeviceCategory
    {
        DB::beginTransaction();
        try {
            $data = [
                'parent_id' => $dto->parentId,
                'icon' => $dto->icon,
            ];

            $deviceCategory = $this->repository->create($data);
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
