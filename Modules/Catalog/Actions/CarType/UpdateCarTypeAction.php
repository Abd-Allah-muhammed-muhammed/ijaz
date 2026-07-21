<?php

namespace Modules\Catalog\Actions\CarType;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\CarTypeRepositoryInterface;
use Modules\Catalog\DTOs\UpdateCarTypeDTO;
use Modules\Catalog\Models\CarType;
use Throwable;

class UpdateCarTypeAction
{
    public function __construct(
        private readonly CarTypeRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(CarType $carType, UpdateCarTypeDTO $dto): CarType
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
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }
}
