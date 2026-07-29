<?php

namespace Modules\Catalog\Actions\PropertyCategory;

use Modules\Catalog\Contracts\Repositories\PropertyCategoryRepositoryInterface;
use Modules\Catalog\Models\PropertyCategory;

class ShowPropertyCategoryAction
{
    public function __construct(
        private readonly PropertyCategoryRepositoryInterface $repository,
    ) {}

    public function handle(PropertyCategory $propertyCategory): PropertyCategory
    {
        return $this->repository->loadForEdit($propertyCategory);
    }
}
