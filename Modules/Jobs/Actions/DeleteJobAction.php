<?php

namespace Modules\Jobs\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Jobs\Contracts\Repositories\JobRepositoryInterface;
use Modules\Jobs\Exceptions\JobsException;
use Modules\Jobs\Http\Controllers\Concerns\AuthorizesJobRequests;
use Modules\Jobs\Models\JobOffer;

class DeleteJobAction
{
    use AuthorizesJobRequests;

    public function __construct(
        private readonly JobRepositoryInterface $repository,
    ) {}

    /**
     * @throws JobsException
     */
    public function handle(JobOffer $job, Model $actor): void
    {
        $this->ensureJobOwnedBy($job, $actor);
        $this->repository->delete($job);
    }
}
