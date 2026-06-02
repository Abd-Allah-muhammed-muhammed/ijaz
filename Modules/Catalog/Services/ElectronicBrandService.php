<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\ElectronicBrandRepositoryInterface;
use Modules\Catalog\Contracts\Services\ElectronicBrandServiceInterface;
use Modules\Catalog\DTOs\StoreElectronicBrandDTO;
use Modules\Catalog\DTOs\UpdateElectronicBrandDTO;
use Modules\Catalog\Models\ElectronicBrand;

class ElectronicBrandService implements ElectronicBrandServiceInterface
{
    public function __construct(
        private readonly ElectronicBrandRepositoryInterface $repository,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }

    public function getAll(Request $request): Collection
    {
        return $this->repository->getAll($request);
    }

    public function store(StoreElectronicBrandDTO $dto): ElectronicBrand
    {
        DB::beginTransaction();
        try {
            $data = [
                'image' => $dto->image,
                'is_active' => $dto->isActive,
            ];

            $electronicBrand = $this->repository->create($data);
            $electronicBrand->translations()->createMany($dto->translations);

            DB::commit();

            return $electronicBrand->load(['translation']);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }

    public function update(ElectronicBrand $electronicBrand, UpdateElectronicBrandDTO $dto): ElectronicBrand
    {
        DB::beginTransaction();
        try {
            $data = [
                'is_active' => $dto->isActive,
            ];

            if ($dto->image) {
                $electronicBrand->deleteImage();
                $data['image'] = $dto->image;
            }

            $electronicBrand = $this->repository->update($electronicBrand, $data);
            $electronicBrand->translations()->delete();
            $electronicBrand->translations()->createMany($dto->translations);

            DB::commit();

            return $electronicBrand->load(['translation']);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }

    public function updateStatus(ElectronicBrand $electronicBrand, bool $isActive): ElectronicBrand
    {
        return $this->repository->updateStatus($electronicBrand, $isActive);
    }

    public function destroy(ElectronicBrand $electronicBrand): void
    {
        $this->repository->delete($electronicBrand);
    }

    public function show(ElectronicBrand $electronicBrand): ElectronicBrand
    {
        return $electronicBrand->load(['translation']);
    }
}
