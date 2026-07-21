<?php

namespace Modules\Catalog\Actions\CarBrand;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\CarBrandRepositoryInterface;
use Modules\Catalog\DTOs\StoreCarBrandDTO;
use Modules\Catalog\Models\CarBrand;
use Throwable;

class StoreCarBrandAction
{
    public function __construct(
        private readonly CarBrandRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreCarBrandDTO $dto): CarBrand
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
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }
}
