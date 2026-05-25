<?php

namespace Modules\Catalog\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\CarTypeRepositoryInterface;
use Modules\Catalog\Models\CarType;
use Modules\Catalog\QueryFilters\CarType\CarTypeFilters;

class CarTypeRepository implements CarTypeRepositoryInterface
{
    public function query(): Builder
    {
        return CarType::with(['translation', 'carBrand.translation']);
    }

    public function paginate(Request $request): LengthAwarePaginator
    {
        $filters = new CarTypeFilters($request);

        return $this->query()
            ->tap(fn (Builder $query) => $filters->apply($query))
            ->paginate($filters->perPage())
            ->withQueryString();
    }

    public function create(array $data): CarType
    {
        return CarType::create($data);
    }

    public function update(CarType $carType, array $data): CarType
    {
        $carType->update($data);

        return $carType->fresh();
    }

    public function delete(CarType $carType): void
    {
        $carType->deleteImage();
        $carType->delete();
    }

    public function findById(int $id): CarType
    {
        return CarType::findOrFail($id);
    }
}
