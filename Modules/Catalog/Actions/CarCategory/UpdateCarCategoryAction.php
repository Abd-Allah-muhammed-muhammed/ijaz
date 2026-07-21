<?php

namespace Modules\Catalog\Actions\CarCategory;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\CarCategoryRepositoryInterface;
use Modules\Catalog\DTOs\UpdateCarCategoryDTO;
use Modules\Catalog\Models\CarCategory;
use Throwable;

class UpdateCarCategoryAction
{
    public function __construct(
        private readonly CarCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(CarCategory $carCategory, UpdateCarCategoryDTO $dto): CarCategory
    {
        DB::beginTransaction();
        try {
            $data = [
                'parent_id' => $dto->parentId,
            ];

            if ($dto->icon) {
                $carCategory->deleteIcon();
                $data['icon'] = $dto->icon;
            }

            $carCategory = $this->repository->update($carCategory, $data);
            $carCategory->translations()->delete();
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
