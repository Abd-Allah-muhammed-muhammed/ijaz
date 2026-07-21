<?php

namespace Modules\Marketplace\Actions\Category;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Marketplace\Contracts\Repositories\CategoryRepositoryInterface;

class ListCategoriesAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateForDashboard($request);
    }
}
