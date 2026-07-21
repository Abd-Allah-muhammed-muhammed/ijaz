<?php

namespace Modules\Catalog\Actions\CarType;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\CarTypeRepositoryInterface;
use Modules\Catalog\DTOs\StoreCarTypeDTO;
use Modules\Catalog\Models\CarType;
use Throwable;

class StoreCarTypeAction
{
    public function __construct(
        private readonly CarTypeRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreCarTypeDTO $dto): CarType
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
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }
}
