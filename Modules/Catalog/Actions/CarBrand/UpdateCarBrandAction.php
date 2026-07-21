<?php

namespace Modules\Catalog\Actions\CarBrand;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\CarBrandRepositoryInterface;
use Modules\Catalog\DTOs\UpdateCarBrandDTO;
use Modules\Catalog\Models\CarBrand;
use Throwable;

class UpdateCarBrandAction
{
    public function __construct(
        private readonly CarBrandRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(CarBrand $carBrand, UpdateCarBrandDTO $dto): CarBrand
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
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }
}
