<?php

namespace Modules\Catalog\Actions\CarType;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\CarTypeRepositoryInterface;

class ListCarTypesAction
{
    public function __construct(
        private readonly CarTypeRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
