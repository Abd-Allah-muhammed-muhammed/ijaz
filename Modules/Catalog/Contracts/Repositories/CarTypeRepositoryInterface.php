<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Catalog\Models\CarType;

interface CarTypeRepositoryInterface
{
    public function query(): Builder;

    public function paginate(Request $request): LengthAwarePaginator;

    public function create(array $data): CarType;

    public function update(CarType $carType, array $data): CarType;

    public function delete(CarType $carType): void;

    public function findById(int $id): CarType;
}
