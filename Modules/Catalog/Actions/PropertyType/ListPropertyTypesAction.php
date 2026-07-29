<?php

namespace Modules\Catalog\Actions\PropertyType;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\PropertyTypeRepositoryInterface;

class ListPropertyTypesAction
{
    public function __construct(
        private readonly PropertyTypeRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateForDashboard($request);
    }
}
