<?php

namespace Modules\Catalog\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Catalog\Models\CarCategory;

interface CarCategoryRepositoryInterface
{
    public function query(): Builder;

    public function paginate(Request $request): LengthAwarePaginator;

    public function create(array $data): CarCategory;

    public function update(CarCategory $carCategory, array $data): CarCategory;

    public function delete(CarCategory $carCategory): void;

    public function findById(int $id): CarCategory;
}
