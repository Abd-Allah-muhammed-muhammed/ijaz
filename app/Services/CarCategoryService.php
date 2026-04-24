<?php

namespace App\Services;

use App\Contracts\Repositories\CarCategoryRepositoryInterface;
use App\Contracts\Services\CarCategoryServiceInterface;
use App\DTOs\CarCategory\StoreCarCategoryDTO;
use App\DTOs\CarCategory\UpdateCarCategoryDTO;
use App\Models\CarCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarCategoryService implements CarCategoryServiceInterface
{
    public function __construct(
        private readonly CarCategoryRepositoryInterface $repository,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }

    public function store(StoreCarCategoryDTO $dto): CarCategory
    {
        DB::beginTransaction();
        try {
            $data = [
                'parent_id' => $dto->parentId,
                'icon' => $dto->icon,
            ];

            $carCategory = $this->repository->create($data);
            $carCategory->translations()->createMany($dto->translations);

            DB::commit();

            return $carCategory->load(['translation']);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }

    public function update(CarCategory $carCategory, UpdateCarCategoryDTO $dto): CarCategory
    {
        DB::beginTransaction();
        try {
            $data = [
                'parent_id' => $dto->parentId,
            ];

            if ($dto->icon) {
                $carCategory->deleteIcon();
                $data['icon'] = $dto->icon;
            }

            $carCategory = $this->repository->update($carCategory, $data);
            $carCategory->translations()->delete();
            $carCategory->translations()->createMany($dto->translations);

            DB::commit();

            return $carCategory->load(['translation']);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }

    public function destroy(CarCategory $carCategory): void
    {
        $this->repository->delete($carCategory);
    }

    public function show(CarCategory $carCategory): CarCategory
    {
        return $carCategory->load(['translation']);
    }
}
