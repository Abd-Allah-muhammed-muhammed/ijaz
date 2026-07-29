<?php

namespace Modules\Marketplace\Actions\Category;

use Modules\Marketplace\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Marketplace\Models\Category;

class ShowCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    public function handle(Category $category): Category
    {
        return $this->repository->loadForEdit($category);
    }
}
