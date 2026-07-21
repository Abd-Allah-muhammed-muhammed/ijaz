<?php

namespace Modules\Catalog\Actions\PropertyType;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Catalog\Contracts\Repositories\PropertyTypeRepositoryInterface;
use Modules\Catalog\QueryFilters\PropertyType\PropertyTypeFilters;

class ListPropertyTypesForApiAction
{
    public function __construct(
        private readonly PropertyTypeRepositoryInterface $repository,
    ) {}

    public function handle(PropertyTypeFilters $filters): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }
}
