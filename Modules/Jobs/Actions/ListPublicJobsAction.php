<?php

namespace Modules\Jobs\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Jobs\Contracts\Repositories\JobRepositoryInterface;

class ListPublicJobsAction
{
    public function __construct(
        private readonly JobRepositoryInterface $repository,
    ) {}

    /**
     * @param  array{search?: string|null, per_page?: int|null}  $filters
     */
    public function handle(array $filters): LengthAwarePaginator
    {
        return $this->repository->listPublic($filters);
    }
}
