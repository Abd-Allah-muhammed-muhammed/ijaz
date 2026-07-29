<?php

namespace Modules\Marketplace\Actions\Category;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Marketplace\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Marketplace\Models\Category;

class ListCategoriesForApiAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateForApi($request);
    }

    public function handleWithNoChildren(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateWithNoChildrenForApi($request);
    }

    public function handleChildren(Category $category, Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateChildrenForApi($category, $request);
    }
}
