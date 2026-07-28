<?php

namespace Modules\Marketplace\Actions\Category;

use App\Support\HandlesTransactionalFileUpload;
use Modules\Marketplace\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Marketplace\DTOs\UpdateCategoryDTO;
use Modules\Marketplace\Enums\CategoryFeesTypeEnum;
use Modules\Marketplace\Models\Category;
use Throwable;

class UpdateCategoryAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    /** @throws Throwable */
    public function handle(Category $category, UpdateCategoryDTO $dto): Category
    {
        return $this->storeFileWithCleanup(
            file: $dto->icon,
            directory: 'categories',
            disk: 'public',
            previousPath: $dto->icon !== null ? $category->icon : null,
            dbWork: function (?string $iconPath) use ($category, $dto): Category {
                $data = [
                    'parent_id' => $dto->parentId,
                    'translations' => $dto->translations,
                    'fees_type' => $dto->feesType,
                ];

                if ($dto->feesType === CategoryFeesTypeEnum::INHERITED) {
                    $data['fees'] = null;
                } else {
                    $data['fees'] = $dto->fees;
                }

                if ($iconPath !== null) {
                    $data['icon'] = $iconPath;
                }

                return $this->repository->update($category, $data);
            },
        );
    }
}
