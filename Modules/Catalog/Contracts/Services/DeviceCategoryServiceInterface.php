<?php

namespace Modules\Catalog\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\DTOs\StoreDeviceCategoryDTO;
use Modules\Catalog\DTOs\UpdateDeviceCategoryDTO;
use Modules\Catalog\Models\DeviceCategory;

interface DeviceCategoryServiceInterface
{
    public function index(Request $request): LengthAwarePaginator;

    /**
     * @return Collection<int, DeviceCategory>
     */
    public function getAll(Request $request): Collection;

    public function store(StoreDeviceCategoryDTO $dto): DeviceCategory;

    public function update(DeviceCategory $deviceCategory, UpdateDeviceCategoryDTO $dto): DeviceCategory;

    public function destroy(DeviceCategory $deviceCategory): void;

    public function show(DeviceCategory $deviceCategory): DeviceCategory;

    /**
     * @return Collection<int, DeviceCategory>
     */
    public function getRootCategories(?int $excludeId = null): Collection;

    /**
     * @return Collection<int, DeviceCategory>
     */
    public function listForSelect(?string $search = null): Collection;
}
