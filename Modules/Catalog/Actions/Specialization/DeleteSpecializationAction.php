<?php

namespace Modules\Catalog\Actions\Specialization;

use Modules\Catalog\Contracts\Repositories\SpecializationRepositoryInterface;
use Modules\Catalog\Models\Specialization;

class DeleteSpecializationAction
{
    public function __construct(
        private readonly SpecializationRepositoryInterface $repository,
    ) {}

    public function handle(Specialization $specialization): void
    {
        $this->repository->delete($specialization);
    }
}
