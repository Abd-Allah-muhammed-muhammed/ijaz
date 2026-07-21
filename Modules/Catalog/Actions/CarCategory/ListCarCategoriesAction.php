<?php

namespace Modules\Catalog\Actions\CarCategory;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\CarCategoryRepositoryInterface;

class ListCarCategoriesAction
{
    public function __construct(
        private readonly CarCategoryRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
