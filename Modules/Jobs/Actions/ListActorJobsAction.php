<?php

namespace Modules\Jobs\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Jobs\Contracts\Repositories\JobRepositoryInterface;

class ListActorJobsAction
{
    public function __construct(
        private readonly JobRepositoryInterface $repository,
    ) {}

    /**
     * @param  array{per_page?: int|null}  $filters
     */
    public function handle(Model $actor, array $filters): LengthAwarePaginator
    {
        return $this->repository->listByActor($actor, $filters);
    }
}
