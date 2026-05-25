<?php

namespace Modules\Catalog\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\CarBrandRepositoryInterface;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\QueryFilters\CarBrand\CarBrandFilters;

class CarBrandRepository implements CarBrandRepositoryInterface
{
    public function query(): Builder
    {
        return CarBrand::with(['translation']);
    }

    public function paginate(Request $request): LengthAwarePaginator
    {
        $filters = new CarBrandFilters($request);

        return $this->query()
            ->tap(fn (Builder $query) => $filters->apply($query))
            ->paginate($filters->perPage())
            ->withQueryString();
    }

    public function create(array $data): CarBrand
    {
        return CarBrand::create($data);
    }

    public function update(CarBrand $carBrand, array $data): CarBrand
    {
        $carBrand->update($data);

        return $carBrand->fresh();
    }

    public function delete(CarBrand $carBrand): void
    {
        $carBrand->deleteImage();
        $carBrand->delete();
    }

    public function findById(int $id): CarBrand
    {
        return CarBrand::findOrFail($id);
    }
}
