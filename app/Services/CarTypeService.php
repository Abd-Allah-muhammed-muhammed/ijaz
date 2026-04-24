<?php

namespace App\Services;

use App\Contracts\Repositories\CarTypeRepositoryInterface;
use App\Contracts\Services\CarTypeServiceInterface;
use App\DTOs\CarType\StoreCarTypeDTO;
use App\DTOs\CarType\UpdateCarTypeDTO;
use App\Models\CarType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarTypeService implements CarTypeServiceInterface
{
    public function __construct(
        private readonly CarTypeRepositoryInterface $repository,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }

    public function store(StoreCarTypeDTO $dto): CarType
    {
        DB::beginTransaction();
        try {
            $data = [
                'is_active' => $dto->isActive,
                'image' => $dto->image,
                'car_brand_id' => $dto->carBrandId,
            ];

            $carType = $this->repository->create($data);
            $carType->translations()->createMany($dto->translations);

            DB::commit();

            return $carType->load(['translation', 'carBrand.translation']);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }

    public function update(CarType $carType, UpdateCarTypeDTO $dto): CarType
    {
        DB::beginTransaction();
        try {
            $data = [
                'is_active' => $dto->isActive,
                'car_brand_id' => $dto->carBrandId,
            ];

            if ($dto->image) {
                $carType->deleteImage();
                $data['image'] = $dto->image;
            }

            $carType = $this->repository->update($carType, $data);
            $carType->translations()->delete();
            $carType->translations()->createMany($dto->translations);

            DB::commit();

            return $carType->load(['translation', 'carBrand.translation']);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }

    public function updateStatus(CarType $carType, bool $isActive): CarType
    {
        DB::beginTransaction();
        try {
            $carType = $this->repository->update($carType, ['is_active' => $isActive]);
            DB::commit();

            return $carType;
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }

    public function destroy(CarType $carType): void
    {
        $this->repository->delete($carType);
    }

    public function show(CarType $carType): CarType
    {
        return $carType->load(['translation', 'carBrand.translation']);
    }
}
