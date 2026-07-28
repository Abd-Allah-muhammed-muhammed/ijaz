<?php

namespace Modules\Catalog\Actions\CarCategory;

use App\Support\HandlesTransactionalFileUpload;
use Modules\Catalog\Contracts\Repositories\CarCategoryRepositoryInterface;
use Modules\Catalog\DTOs\UpdateCarCategoryDTO;
use Modules\Catalog\Models\CarCategory;
use Throwable;

class UpdateCarCategoryAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly CarCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(CarCategory $carCategory, UpdateCarCategoryDTO $dto): CarCategory
    {
        return $this->storeFileWithCleanup(
            file: $dto->icon,
            directory: 'car_categories',
            disk: null,
            previousPath: $dto->icon !== null ? $carCategory->icon : null,
            dbWork: function (?string $iconPath) use ($dto, $carCategory): CarCategory {
                $data = [
                    'parent_id' => $dto->parentId,
                ];

                if ($iconPath !== null) {
                    $data['icon'] = $iconPath;
                }

                $carCategory = $this->repository->update($carCategory, $data);
                $carCategory->translations()->delete();
                $carCategory->translations()->createMany($dto->translations);

                return $carCategory->load(['translation']);
            },
        );
    }
}
