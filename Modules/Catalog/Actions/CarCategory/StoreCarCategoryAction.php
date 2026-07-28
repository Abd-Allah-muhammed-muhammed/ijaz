<?php

namespace Modules\Catalog\Actions\CarCategory;

use App\Support\HandlesTransactionalFileUpload;
use Modules\Catalog\Contracts\Repositories\CarCategoryRepositoryInterface;
use Modules\Catalog\DTOs\StoreCarCategoryDTO;
use Modules\Catalog\Models\CarCategory;
use Throwable;

class StoreCarCategoryAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly CarCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreCarCategoryDTO $dto): CarCategory
    {
        return $this->storeFileWithCleanup(
            file: $dto->icon,
            directory: 'car_categories',
            disk: null,
            dbWork: function (?string $iconPath) use ($dto): CarCategory {
                $carCategory = $this->repository->create([
                    'parent_id' => $dto->parentId,
                    'icon' => $iconPath,
                ]);
                $carCategory->translations()->createMany($dto->translations);

                return $carCategory->load(['translation']);
            },
        );
    }
}
