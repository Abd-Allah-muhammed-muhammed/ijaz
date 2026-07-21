<?php

namespace Modules\Catalog\Actions\CarCategory;

use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Contracts\Repositories\CarCategoryRepositoryInterface;
use Modules\Catalog\Models\CarCategory;

class ListRootCarCategoriesAction
{
    public function __construct(
        private readonly CarCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, CarCategory>
     */
    public function handle(): Collection
    {
        return $this->repository->getRootCategories();
    }
}
