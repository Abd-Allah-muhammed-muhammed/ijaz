<?php

namespace App\Contracts\Services;

use App\DTOs\CarCategory\StoreCarCategoryDTO;
use App\DTOs\CarCategory\UpdateCarCategoryDTO;
use App\Models\CarCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

interface CarCategoryServiceInterface
{
    public function index(Request $request): LengthAwarePaginator;

    public function store(StoreCarCategoryDTO $dto): CarCategory;

    public function update(CarCategory $carCategory, UpdateCarCategoryDTO $dto): CarCategory;

    public function destroy(CarCategory $carCategory): void;

    public function show(CarCategory $carCategory): CarCategory;
}
