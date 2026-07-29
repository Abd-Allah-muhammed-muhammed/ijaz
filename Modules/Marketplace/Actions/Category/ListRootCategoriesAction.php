<?php

namespace Modules\Marketplace\Actions\Category;

use Illuminate\Database\Eloquent\Collection;
use Modules\Marketplace\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Marketplace\Models\Category;

class ListRootCategoriesAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    /** @return Collection<int, Category> */
    public function handle(): Collection
    {
        return $this->repository->getRootCategories();
    }
}
