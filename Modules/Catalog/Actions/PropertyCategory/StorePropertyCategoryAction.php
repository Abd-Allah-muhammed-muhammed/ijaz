<?php

namespace Modules\Catalog\Actions\PropertyCategory;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\PropertyCategoryRepositoryInterface;
use Modules\Catalog\DTOs\StorePropertyCategoryDTO;
use Modules\Catalog\Models\PropertyCategory;
use Throwable;

class StorePropertyCategoryAction
{
    public function __construct(
        private readonly PropertyCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StorePropertyCategoryDTO $dto): PropertyCategory
    {
        return DB::transaction(fn (): PropertyCategory => $this->repository->create([
            'translations' => $dto->translations,
            'parent_id' => $dto->parentId,
            'is_active' => $dto->isActive,
        ]));
    }
}
