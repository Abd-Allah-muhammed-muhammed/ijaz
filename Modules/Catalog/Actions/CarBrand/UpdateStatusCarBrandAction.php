<?php

namespace Modules\Catalog\Actions\CarBrand;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\CarBrandRepositoryInterface;
use Modules\Catalog\Models\CarBrand;
use Throwable;

class UpdateStatusCarBrandAction
{
    public function __construct(
        private readonly CarBrandRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(CarBrand $carBrand, bool $isActive): CarBrand
    {
        DB::beginTransaction();
        try {
            $carBrand = $this->repository->update($carBrand, ['is_active' => $isActive]);
            DB::commit();

            return $carBrand;
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }
}
