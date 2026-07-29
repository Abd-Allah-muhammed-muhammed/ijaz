<?php

namespace Modules\Catalog\Actions\Specialization;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\SpecializationRepositoryInterface;

class ListSpecializationsAction
{
    public function __construct(
        private readonly SpecializationRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
