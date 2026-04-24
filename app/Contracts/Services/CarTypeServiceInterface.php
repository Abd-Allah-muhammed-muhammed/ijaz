<?php

namespace App\Contracts\Services;

use App\DTOs\CarType\StoreCarTypeDTO;
use App\DTOs\CarType\UpdateCarTypeDTO;
use App\Models\CarType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

interface CarTypeServiceInterface
{
    public function index(Request $request): LengthAwarePaginator;

    public function store(StoreCarTypeDTO $dto): CarType;

    public function update(CarType $carType, UpdateCarTypeDTO $dto): CarType;

    public function updateStatus(CarType $carType, bool $isActive): CarType;

    public function destroy(CarType $carType): void;

    public function show(CarType $carType): CarType;
}
