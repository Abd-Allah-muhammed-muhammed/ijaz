<?php

namespace App\Services;

use App\Contracts\Repositories\CarBrandRepositoryInterface;
use App\Contracts\Services\CarBrandServiceInterface;
use App\DTOs\CarBrand\StoreCarBrandDTO;
use App\DTOs\CarBrand\UpdateCarBrandDTO;
use App\Models\CarBrand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarBrandService implements CarBrandServiceInterface
{
    public function __construct(
        private readonly CarBrandRepositoryInterface $repository,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }

    public function store(StoreCarBrandDTO $dto): CarBrand
    {
        DB::beginTransaction();
        try {
            $data = [
                'is_active' => $dto->isActive,
                'image' => $dto->image,
            ];

            $carBrand = $this->repository->create($data);
            $carBrand->translations()->createMany($dto->translations);

            DB::commit();

            return $carBrand->load(['translation']);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }

    public function update(CarBrand $carBrand, UpdateCarBrandDTO $dto): CarBrand
    {
        DB::beginTransaction();
        try {
            $data = [
                'is_active' => $dto->isActive,
            ];

            if ($dto->image) {
                $carBrand->deleteImage();
                $data['image'] = $dto->image;
            }

            $carBrand = $this->repository->update($carBrand, $data);
            $carBrand->translations()->delete();
            $carBrand->translations()->createMany($dto->translations);

            DB::commit();

            return $carBrand->load(['translation']);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }

    public function updateStatus(CarBrand $carBrand, bool $isActive): CarBrand
    {
        DB::beginTransaction();
        try {
            $carBrand = $this->repository->update($carBrand, ['is_active' => $isActive]);
            DB::commit();

            return $carBrand;
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }

    public function destroy(CarBrand $carBrand): void
    {
        $this->repository->delete($carBrand);
    }

    public function show(CarBrand $carBrand): CarBrand
    {
        return $carBrand->load(['translation']);
    }
}
