<?php

namespace Modules\Marketplace\Actions\Category;

use Illuminate\Support\Facades\DB;
use Modules\Marketplace\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Marketplace\DTOs\StoreCategoryDTO;
use Modules\Marketplace\Enums\CategoryFeesTypeEnum;
use Modules\Marketplace\Models\Category;
use Throwable;

class StoreCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    /** @throws Throwable */
    public function handle(StoreCategoryDTO $dto): Category
    {
        return DB::transaction(function () use ($dto): Category {
            $data = [
                'parent_id' => $dto->parentId,
                'translations' => $dto->translations,
                'fees_type' => $dto->feesType,
                'icon' => $dto->icon->store('categories'),
            ];

            if ($dto->feesType !== CategoryFeesTypeEnum::INHERITED) {
                $data['fees'] = $dto->fees;
            }

            return $this->repository->create($data);
        });
    }
}
