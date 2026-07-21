<?php

namespace Modules\Catalog\Actions\CarType;

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

            return $carType;
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }
}
