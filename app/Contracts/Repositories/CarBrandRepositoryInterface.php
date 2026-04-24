<?php

namespace App\Contracts\Repositories;

use App\Models\CarBrand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface CarBrandRepositoryInterface
{
    public function query(): Builder;

    public function paginate(Request $request): LengthAwarePaginator;

    public function create(array $data): CarBrand;

    public function update(CarBrand $carBrand, array $data): CarBrand;

    public function delete(CarBrand $carBrand): void;

    public function findById(int $id): CarBrand;
}
