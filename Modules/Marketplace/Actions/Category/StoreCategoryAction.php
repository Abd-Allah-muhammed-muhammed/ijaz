<?php

namespace Modules\Marketplace\Actions\Category;

use App\Support\HandlesTransactionalFileUpload;
use Modules\Marketplace\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Marketplace\DTOs\StoreCategoryDTO;
use Modules\Marketplace\Enums\CategoryFeesTypeEnum;
use Modules\Marketplace\Models\Category;
use Throwable;

class StoreCategoryAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    /** @throws Throwable */
    public function handle(StoreCategoryDTO $dto): Category
    {
        return $this->storeFileWithCleanup(
            file: $dto->icon,
            directory: 'categories',
            disk: 'public',
            dbWork: function (?string $iconPath) use ($dto): Category {
                $data = [
                    'parent_id' => $dto->parentId,
                    'translations' => $dto->translations,
                    'fees_type' => $dto->feesType,
                    'icon' => $iconPath,
                ];

                if ($dto->feesType !== CategoryFeesTypeEnum::INHERITED) {
                    $data['fees'] = $dto->fees;
                }

                return $this->repository->create($data);
            },
        );
    }
}
