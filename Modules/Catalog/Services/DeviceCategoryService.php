<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Actions\DeviceCategory\DeleteDeviceCategoryAction;
use Modules\Catalog\Actions\DeviceCategory\FindDeviceCategoryAction;
use Modules\Catalog\Actions\DeviceCategory\ListAllDeviceCategoriesAction;
use Modules\Catalog\Actions\DeviceCategory\ListDeviceCategoriesAction;
use Modules\Catalog\Actions\DeviceCategory\ListDeviceCategoriesForSelectAction;
use Modules\Catalog\Actions\DeviceCategory\ListRootDeviceCategoriesAction;
use Modules\Catalog\Actions\DeviceCategory\ShowDeviceCategoryAction;
use Modules\Catalog\Actions\DeviceCategory\StoreDeviceCategoryAction;
use Modules\Catalog\Actions\DeviceCategory\UpdateDeviceCategoryAction;
use Modules\Catalog\Contracts\Services\DeviceCategoryServiceInterface;
use Modules\Catalog\DTOs\StoreDeviceCategoryDTO;
use Modules\Catalog\DTOs\UpdateDeviceCategoryDTO;
use Modules\Catalog\Models\DeviceCategory;

class DeviceCategoryService implements DeviceCategoryServiceInterface
{
    public function __construct(
        private readonly ListDeviceCategoriesAction $listAction,
        private readonly ListAllDeviceCategoriesAction $listAllAction,
        private readonly ListDeviceCategoriesForSelectAction $listForSelectAction,
        private readonly StoreDeviceCategoryAction $storeAction,
        private readonly UpdateDeviceCategoryAction $updateAction,
        private readonly DeleteDeviceCategoryAction $deleteAction,
        private readonly ShowDeviceCategoryAction $showAction,
        private readonly FindDeviceCategoryAction $findAction,
        private readonly ListRootDeviceCategoriesAction $listRootAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function getAll(Request $request): Collection
    {
        return $this->listAllAction->handle($request);
    }

    public function store(StoreDeviceCategoryDTO $dto): DeviceCategory
    {
        return $this->storeAction->handle($dto);
    }

    public function update(DeviceCategory $deviceCategory, UpdateDeviceCategoryDTO $dto): DeviceCategory
    {
        return $this->updateAction->handle($deviceCategory, $dto);
    }

    public function destroy(DeviceCategory $deviceCategory): void
    {
        $this->deleteAction->handle($deviceCategory);
    }

    public function show(DeviceCategory $deviceCategory): DeviceCategory
    {
        return $this->showAction->handle($deviceCategory);
    }

    public function findById(int $id): ?DeviceCategory
    {
        return $this->findAction->handle($id);
    }

    /**
     * @return Collection<int, DeviceCategory>
     */
    public function getRootCategories(?int $excludeId = null): Collection
    {
        return $this->listRootAction->handle($excludeId);
    }

    /**
     * @return Collection<int, DeviceCategory>
     */
    public function listForSelect(?string $search = null): Collection
    {
        return $this->listForSelectAction->handle($search);
    }
}
