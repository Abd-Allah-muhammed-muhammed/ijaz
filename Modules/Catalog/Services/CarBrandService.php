<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Catalog\Actions\CarBrand\DeleteCarBrandAction;
use Modules\Catalog\Actions\CarBrand\ListCarBrandsAction;
use Modules\Catalog\Actions\CarBrand\ShowCarBrandAction;
use Modules\Catalog\Actions\CarBrand\StoreCarBrandAction;
use Modules\Catalog\Actions\CarBrand\UpdateCarBrandAction;
use Modules\Catalog\Actions\CarBrand\UpdateStatusCarBrandAction;
use Modules\Catalog\Contracts\Services\CarBrandServiceInterface;
use Modules\Catalog\DTOs\StoreCarBrandDTO;
use Modules\Catalog\DTOs\UpdateCarBrandDTO;
use Modules\Catalog\Models\CarBrand;

class CarBrandService implements CarBrandServiceInterface
{
    public function __construct(
        private readonly ListCarBrandsAction $listAction,
        private readonly StoreCarBrandAction $storeAction,
        private readonly UpdateCarBrandAction $updateAction,
        private readonly UpdateStatusCarBrandAction $updateStatusAction,
        private readonly DeleteCarBrandAction $deleteAction,
        private readonly ShowCarBrandAction $showAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function store(StoreCarBrandDTO $dto): CarBrand
    {
        return $this->storeAction->handle($dto);
    }

    public function update(CarBrand $carBrand, UpdateCarBrandDTO $dto): CarBrand
    {
        return $this->updateAction->handle($carBrand, $dto);
    }

    public function updateStatus(CarBrand $carBrand, bool $isActive): CarBrand
    {
        return $this->updateStatusAction->handle($carBrand, $isActive);
    }

    public function destroy(CarBrand $carBrand): void
    {
        $this->deleteAction->handle($carBrand);
    }

    public function show(CarBrand $carBrand): CarBrand
    {
        return $this->showAction->handle($carBrand);
    }
}
