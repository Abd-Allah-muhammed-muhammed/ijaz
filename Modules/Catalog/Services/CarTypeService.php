<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Catalog\Actions\CarType\DeleteCarTypeAction;
use Modules\Catalog\Actions\CarType\ListCarTypesAction;
use Modules\Catalog\Actions\CarType\ShowCarTypeAction;
use Modules\Catalog\Actions\CarType\StoreCarTypeAction;
use Modules\Catalog\Actions\CarType\UpdateCarTypeAction;
use Modules\Catalog\Actions\CarType\UpdateStatusCarTypeAction;
use Modules\Catalog\Contracts\Services\CarTypeServiceInterface;
use Modules\Catalog\DTOs\StoreCarTypeDTO;
use Modules\Catalog\DTOs\UpdateCarTypeDTO;
use Modules\Catalog\Models\CarType;

class CarTypeService implements CarTypeServiceInterface
{
    public function __construct(
        private readonly ListCarTypesAction $listAction,
        private readonly StoreCarTypeAction $storeAction,
        private readonly UpdateCarTypeAction $updateAction,
        private readonly UpdateStatusCarTypeAction $updateStatusAction,
        private readonly DeleteCarTypeAction $deleteAction,
        private readonly ShowCarTypeAction $showAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function store(StoreCarTypeDTO $dto): CarType
    {
        return $this->storeAction->handle($dto);
    }

    public function update(CarType $carType, UpdateCarTypeDTO $dto): CarType
    {
        return $this->updateAction->handle($carType, $dto);
    }

    public function updateStatus(CarType $carType, bool $isActive): CarType
    {
        return $this->updateStatusAction->handle($carType, $isActive);
    }

    public function destroy(CarType $carType): void
    {
        $this->deleteAction->handle($carType);
    }

    public function show(CarType $carType): CarType
    {
        return $this->showAction->handle($carType);
    }
}
