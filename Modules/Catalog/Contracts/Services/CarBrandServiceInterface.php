<?php

namespace Modules\Catalog\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\DTOs\StoreCarBrandDTO;
use Modules\Catalog\DTOs\UpdateCarBrandDTO;
use Modules\Catalog\Models\CarBrand;

interface CarBrandServiceInterface
{
    public function index(Request $request): LengthAwarePaginator;

    public function store(StoreCarBrandDTO $dto): CarBrand;

    public function update(CarBrand $carBrand, UpdateCarBrandDTO $dto): CarBrand;

    public function updateStatus(CarBrand $carBrand, bool $isActive): CarBrand;

    public function destroy(CarBrand $carBrand): void;

    public function show(CarBrand $carBrand): CarBrand;

    /**
     * @return Collection<int, CarBrand>
     */
    public function listForSelect(?string $search = null): Collection;
}
