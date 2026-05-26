<?php

namespace Modules\Catalog\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\ElectronicBrandRepositoryInterface;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Catalog\QueryFilters\ElectronicBrand\ElectronicBrandFilters;

class ElectronicBrandRepository implements ElectronicBrandRepositoryInterface
{
    public function query(): Builder
    {
        return ElectronicBrand::with(['translation']);
    }

    public function paginate(Request $request): LengthAwarePaginator
    {
        $filters = new ElectronicBrandFilters($request);

        return $this->query()
            ->tap(fn (Builder $query) => $filters->apply($query))
            ->paginate($filters->perPage())
            ->withQueryString();
    }

    public function create(array $data): ElectronicBrand
    {
        return ElectronicBrand::create($data);
    }

    public function update(ElectronicBrand $electronicBrand, array $data): ElectronicBrand
    {
        $electronicBrand->update($data);

        return $electronicBrand->fresh();
    }

    public function delete(ElectronicBrand $electronicBrand): void
    {
        $electronicBrand->deleteImage();
        $electronicBrand->delete();
    }

    public function findById(int $id): ElectronicBrand
    {
        return ElectronicBrand::findOrFail($id);
    }

    public function updateStatus(ElectronicBrand $electronicBrand, bool $isActive): ElectronicBrand
    {
        $electronicBrand->update(['is_active' => $isActive]);

        return $electronicBrand->fresh();
    }
}
