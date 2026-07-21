<?php

namespace Modules\Catalog\Actions\PropertyCategory;

use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Contracts\Repositories\PropertyCategoryRepositoryInterface;
use Modules\Catalog\Models\PropertiyCategory;

class ListRootPropertyCategoriesAction
{
    public function __construct(
        private readonly PropertyCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, PropertiyCategory>
     */
    public function handle(): Collection
    {
        return $this->repository->getRootCategories();
    }
}
