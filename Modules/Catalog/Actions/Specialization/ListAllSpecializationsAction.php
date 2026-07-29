<?php

namespace Modules\Catalog\Actions\Specialization;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\SpecializationRepositoryInterface;
use Modules\Catalog\Models\Specialization;

class ListAllSpecializationsAction
{
    public function __construct(
        private readonly SpecializationRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Specialization>
     */
    public function handle(Request $request): Collection
    {
        return $this->repository->getAll($request);
    }
}
