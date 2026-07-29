<?php

namespace Modules\Catalog\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\DTOs\StoreCarTypeDTO;
use Modules\Catalog\DTOs\UpdateCarTypeDTO;
use Modules\Catalog\Models\CarType;

interface CarTypeServiceInterface
{
    public function index(Request $request): LengthAwarePaginator;

    public function store(StoreCarTypeDTO $dto): CarType;

    public function update(CarType $carType, UpdateCarTypeDTO $dto): CarType;

    public function updateStatus(CarType $carType, bool $isActive): CarType;

    public function destroy(CarType $carType): void;

    public function show(CarType $carType): CarType;

    /**
     * @return Collection<int, CarType>
     */
    public function listForSelect(?string $search = null, int $carBrandId = 0): Collection;
}
