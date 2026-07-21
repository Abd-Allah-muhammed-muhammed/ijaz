<?php

namespace Modules\Catalog\Contracts\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\DTOs\StoreCarCategoryDTO;
use Modules\Catalog\DTOs\UpdateCarCategoryDTO;
use Modules\Catalog\Models\CarCategory;

interface CarCategoryServiceInterface
{
    public function index(Request $request): LengthAwarePaginator;

    public function store(StoreCarCategoryDTO $dto): CarCategory;

    public function update(CarCategory $carCategory, UpdateCarCategoryDTO $dto): CarCategory;

    public function destroy(CarCategory $carCategory): void;

    public function show(CarCategory $carCategory): CarCategory;

    /**
     * @return Collection<int, CarCategory>
     */
    public function getRootCategories(): Collection;
}
