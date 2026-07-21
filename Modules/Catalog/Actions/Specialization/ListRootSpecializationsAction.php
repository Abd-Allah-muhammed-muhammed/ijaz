<?php

namespace Modules\Catalog\Actions\Specialization;

use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Contracts\Repositories\SpecializationRepositoryInterface;
use Modules\Catalog\Models\Specialization;

class ListRootSpecializationsAction
{
    public function __construct(
        private readonly SpecializationRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Specialization>
     */
    public function handle(?int $excludeId = null): Collection
    {
        return $this->repository->getRootSpecializations($excludeId);
    }
}
