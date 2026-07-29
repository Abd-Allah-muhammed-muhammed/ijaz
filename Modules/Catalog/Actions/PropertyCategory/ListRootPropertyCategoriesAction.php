<?php

namespace Modules\Catalog\Actions\PropertyCategory;

use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Contracts\Repositories\PropertyCategoryRepositoryInterface;
use Modules\Catalog\Models\PropertyCategory;

class ListRootPropertyCategoriesAction
{
    public function __construct(
        private readonly PropertyCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, PropertyCategory>
     */
    public function handle(?int $excludeId = null): Collection
    {
        return $this->repository->getRootCategories($excludeId);
    }
}
