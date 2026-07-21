<?php

namespace Modules\Catalog\Actions\PropertyCategory;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Catalog\Contracts\Repositories\PropertyCategoryRepositoryInterface;
use Modules\Catalog\QueryFilters\PropertyCategory\PropertyCategoryFilters;

class ListPropertyCategoriesForApiAction
{
    public function __construct(
        private readonly PropertyCategoryRepositoryInterface $repository,
    ) {}

    public function handle(PropertyCategoryFilters $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }
}
