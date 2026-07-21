<?php

namespace Modules\Catalog\Actions\CarCategory;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\CarCategoryRepositoryInterface;
use Modules\Catalog\DTOs\StoreCarCategoryDTO;
use Modules\Catalog\Models\CarCategory;
use Throwable;

class StoreCarCategoryAction
{
    public function __construct(
        private readonly CarCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreCarCategoryDTO $dto): CarCategory
    {
        DB::beginTransaction();
        try {
            $data = [
                'parent_id' => $dto->parentId,
                'icon' => $dto->icon,
            ];

            $carCategory = $this->repository->create($data);
            $carCategory->translations()->createMany($dto->translations);

            DB::commit();

            return $carCategory->load(['translation']);
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }
}
