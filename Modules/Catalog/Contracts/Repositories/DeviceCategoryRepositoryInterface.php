<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Catalog\Models\DeviceCategory;

interface DeviceCategoryRepositoryInterface
{
    public function query(): Builder;

    public function paginate(Request $request): LengthAwarePaginator;

    public function create(array $data): DeviceCategory;

    public function update(DeviceCategory $deviceCategory, array $data): DeviceCategory;

    public function delete(DeviceCategory $deviceCategory): void;

    public function findById(int $id): DeviceCategory;
}
