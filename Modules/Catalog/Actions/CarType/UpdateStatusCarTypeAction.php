<?php

namespace Modules\Catalog\Actions\CarType;

use App\Support\LookupCache;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\CarTypeRepositoryInterface;
use Modules\Catalog\Models\CarType;
use Throwable;

class UpdateStatusCarTypeAction
{
    public function __construct(
        private readonly CarTypeRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(CarType $carType, bool $isActive): CarType
    {
        DB::beginTransaction();
        try {
            $carType = $this->repository->update($carType, ['is_active' => $isActive]);
            DB::commit();

            LookupCache::forgetScopedAllLocales('car-types:by-brand', (int) $carType->car_brand_id);
            LookupCache::forgetScopedAllLocales('car-types:by-brand', 0);

            return $carType;
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }
}
