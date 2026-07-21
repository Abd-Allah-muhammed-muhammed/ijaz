<?php

namespace Modules\Catalog\Actions\ElectronicBrand;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\ElectronicBrandRepositoryInterface;
use Modules\Catalog\DTOs\UpdateElectronicBrandDTO;
use Modules\Catalog\Models\ElectronicBrand;
use Throwable;

class UpdateElectronicBrandAction
{
    public function __construct(
        private readonly ElectronicBrandRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(ElectronicBrand $electronicBrand, UpdateElectronicBrandDTO $dto): ElectronicBrand
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
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }
}
