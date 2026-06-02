<?php

namespace Modules\Catalog\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\DTOs\StoreElectronicBrandDTO;
use Modules\Catalog\DTOs\UpdateElectronicBrandDTO;
use Modules\Catalog\Models\ElectronicBrand;

interface ElectronicBrandServiceInterface
{
    public function index(Request $request): LengthAwarePaginator;

    /**
     * @return Collection<int, ElectronicBrand>
     */
    public function getAll(Request $request): Collection;

    public function store(StoreElectronicBrandDTO $dto): ElectronicBrand;

    public function update(ElectronicBrand $electronicBrand, UpdateElectronicBrandDTO $dto): ElectronicBrand;

    public function updateStatus(ElectronicBrand $electronicBrand, bool $isActive): ElectronicBrand;

    public function destroy(ElectronicBrand $electronicBrand): void;

    public function show(ElectronicBrand $electronicBrand): ElectronicBrand;
}
