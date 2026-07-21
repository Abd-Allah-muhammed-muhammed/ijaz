<?php

namespace Modules\Marketplace\Actions\Category;

use App\Enums\CategoryFeesTypeEnum;
use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Marketplace\DTOs\UpdateCategoryDTO;
use Modules\Marketplace\Models\Category;
use Throwable;

class UpdateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    /** @throws Throwable */
    public function handle(Category $category, UpdateCategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($category, $dto): Category {
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

            if ($dto->icon !== null) {
                $category->deleteIcon();
                $data['icon'] = $dto->icon->store('categories');
            }

            return $this->repository->update($category, $data);
        });
    }
}
