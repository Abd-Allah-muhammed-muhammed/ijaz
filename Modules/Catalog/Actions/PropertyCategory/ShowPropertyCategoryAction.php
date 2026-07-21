<?php

namespace Modules\Catalog\Actions\PropertyCategory;

use Modules\Catalog\Contracts\Repositories\PropertyCategoryRepositoryInterface;
use Modules\Catalog\Models\PropertiyCategory;

class ShowPropertyCategoryAction
{
    public function __construct(
        private readonly PropertyCategoryRepositoryInterface $repository,
    ) {}

    public function handle(PropertiyCategory $propertyCategory): PropertiyCategory
    {
        return $this->repository->loadForEdit($propertyCategory);
    }
}
