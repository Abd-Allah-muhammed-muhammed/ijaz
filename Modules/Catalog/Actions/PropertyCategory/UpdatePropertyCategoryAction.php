<?php

namespace Modules\Catalog\Actions\PropertyCategory;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\PropertyCategoryRepositoryInterface;
use Modules\Catalog\DTOs\UpdatePropertyCategoryDTO;
use Modules\Catalog\Models\PropertyCategory;
use Throwable;

class UpdatePropertyCategoryAction
{
    public function __construct(
        private readonly PropertyCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(PropertyCategory $propertyCategory, UpdatePropertyCategoryDTO $dto): PropertyCategory
    {
        return DB::transaction(function () use ($propertyCategory, $dto): PropertyCategory {
            $data = [
                'translations' => $dto->translations,
                'parent_id' => $dto->parentId,
            ];

            if ($dto->isActive !== null) {
                $data['is_active'] = $dto->isActive;
            }

            return $this->repository->update($propertyCategory, $data);
        });
    }
}
