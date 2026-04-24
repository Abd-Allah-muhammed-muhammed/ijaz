<?php

namespace App\Contracts\Services;

use App\DTOs\CarBrand\StoreCarBrandDTO;
use App\DTOs\CarBrand\UpdateCarBrandDTO;
use App\Models\CarBrand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

interface CarBrandServiceInterface
{
    public function index(Request $request): LengthAwarePaginator;

    public function store(StoreCarBrandDTO $dto): CarBrand;

    public function update(CarBrand $carBrand, UpdateCarBrandDTO $dto): CarBrand;

    public function updateStatus(CarBrand $carBrand, bool $isActive): CarBrand;

    public function destroy(CarBrand $carBrand): void;

    public function show(CarBrand $carBrand): CarBrand;
}
