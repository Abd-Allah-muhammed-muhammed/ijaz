<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Models\ElectronicBrand;

interface ElectronicBrandRepositoryInterface
{
    public function query(): Builder;

    public function paginate(Request $request): LengthAwarePaginator;

    /**
     * @return Collection<int, ElectronicBrand>
     */
    public function getAll(Request $request): Collection;

    public function create(array $data): ElectronicBrand;

    public function update(ElectronicBrand $electronicBrand, array $data): ElectronicBrand;

    public function delete(ElectronicBrand $electronicBrand): void;

    public function findById(int $id): ElectronicBrand;

    public function updateStatus(ElectronicBrand $electronicBrand, bool $isActive): ElectronicBrand;
}
