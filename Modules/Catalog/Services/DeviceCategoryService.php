<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\DeviceCategoryRepositoryInterface;
use Modules\Catalog\Contracts\Services\DeviceCategoryServiceInterface;
use Modules\Catalog\DTOs\StoreDeviceCategoryDTO;
use Modules\Catalog\DTOs\UpdateDeviceCategoryDTO;
use Modules\Catalog\Models\DeviceCategory;

class DeviceCategoryService implements DeviceCategoryServiceInterface
{
    public function __construct(
        private readonly DeviceCategoryRepositoryInterface $repository,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }

    public function store(StoreDeviceCategoryDTO $dto): DeviceCategory
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
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }

    public function update(DeviceCategory $deviceCategory, UpdateDeviceCategoryDTO $dto): DeviceCategory
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
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }

    public function destroy(DeviceCategory $deviceCategory): void
    {
        $this->repository->delete($deviceCategory);
    }

    public function show(DeviceCategory $deviceCategory): DeviceCategory
    {
        return $deviceCategory->load(['translation']);
    }
}
