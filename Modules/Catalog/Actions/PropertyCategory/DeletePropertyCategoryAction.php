<?php

namespace Modules\Catalog\Actions\PropertyCategory;

use Modules\Catalog\Contracts\Repositories\PropertyCategoryRepositoryInterface;
use Modules\Catalog\Models\PropertiyCategory;

class DeletePropertyCategoryAction
{
    public function __construct(
        private readonly PropertyCategoryRepositoryInterface $repository,
    ) {}

    public function handle(PropertiyCategory $propertyCategory): void
    {
        $this->repository->delete($propertyCategory);
    }
}
