<?php

namespace Modules\Catalog\Actions\Specialization;

use Modules\Catalog\Contracts\Repositories\SpecializationRepositoryInterface;
use Modules\Catalog\Models\Specialization;

class FindSpecializationAction
{
    public function __construct(
        private readonly SpecializationRepositoryInterface $repository,
    ) {}

    public function handle(int $id): ?Specialization
    {
        return $this->repository->find($id);
    }
}
