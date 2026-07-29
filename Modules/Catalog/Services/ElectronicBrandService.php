<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Actions\ElectronicBrand\DeleteElectronicBrandAction;
use Modules\Catalog\Actions\ElectronicBrand\FindElectronicBrandAction;
use Modules\Catalog\Actions\ElectronicBrand\ListAllElectronicBrandsAction;
use Modules\Catalog\Actions\ElectronicBrand\ListElectronicBrandsAction;
use Modules\Catalog\Actions\ElectronicBrand\ListElectronicBrandsForSelectAction;
use Modules\Catalog\Actions\ElectronicBrand\ShowElectronicBrandAction;
use Modules\Catalog\Actions\ElectronicBrand\StoreElectronicBrandAction;
use Modules\Catalog\Actions\ElectronicBrand\UpdateElectronicBrandAction;
use Modules\Catalog\Actions\ElectronicBrand\UpdateStatusElectronicBrandAction;
use Modules\Catalog\Contracts\Services\ElectronicBrandServiceInterface;
use Modules\Catalog\DTOs\StoreElectronicBrandDTO;
use Modules\Catalog\DTOs\UpdateElectronicBrandDTO;
use Modules\Catalog\Models\ElectronicBrand;

class ElectronicBrandService implements ElectronicBrandServiceInterface
{
    public function __construct(
        private readonly ListElectronicBrandsAction $listAction,
        private readonly ListAllElectronicBrandsAction $listAllAction,
        private readonly ListElectronicBrandsForSelectAction $listForSelectAction,
        private readonly StoreElectronicBrandAction $storeAction,
        private readonly UpdateElectronicBrandAction $updateAction,
        private readonly UpdateStatusElectronicBrandAction $updateStatusAction,
        private readonly DeleteElectronicBrandAction $deleteAction,
        private readonly ShowElectronicBrandAction $showAction,
        private readonly FindElectronicBrandAction $findAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function getAll(Request $request): Collection
    {
        return $this->listAllAction->handle($request);
    }

    public function store(StoreElectronicBrandDTO $dto): ElectronicBrand
    {
        return $this->storeAction->handle($dto);
    }

    public function update(ElectronicBrand $electronicBrand, UpdateElectronicBrandDTO $dto): ElectronicBrand
    {
        return $this->updateAction->handle($electronicBrand, $dto);
    }

    public function updateStatus(ElectronicBrand $electronicBrand, bool $isActive): ElectronicBrand
    {
        return $this->updateStatusAction->handle($electronicBrand, $isActive);
    }

    public function destroy(ElectronicBrand $electronicBrand): void
    {
        $this->deleteAction->handle($electronicBrand);
    }

    public function show(ElectronicBrand $electronicBrand): ElectronicBrand
    {
        return $this->showAction->handle($electronicBrand);
    }

    public function findById(int $id): ?ElectronicBrand
    {
        return $this->findAction->handle($id);
    }

    /**
     * @return Collection<int, ElectronicBrand>
     */
    public function listForSelect(?string $search = null): Collection
    {
        return $this->listForSelectAction->handle($search);
    }
}
