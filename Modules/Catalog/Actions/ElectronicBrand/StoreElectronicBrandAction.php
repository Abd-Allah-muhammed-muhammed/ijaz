<?php

namespace Modules\Catalog\Actions\ElectronicBrand;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\ElectronicBrandRepositoryInterface;
use Modules\Catalog\DTOs\StoreElectronicBrandDTO;
use Modules\Catalog\Models\ElectronicBrand;
use Throwable;

class StoreElectronicBrandAction
{
    public function __construct(
        private readonly ElectronicBrandRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreElectronicBrandDTO $dto): ElectronicBrand
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
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }
}
