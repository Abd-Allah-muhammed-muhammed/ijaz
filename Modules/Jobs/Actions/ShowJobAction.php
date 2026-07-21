<?php

namespace Modules\Jobs\Actions;

use Modules\Jobs\Contracts\Repositories\JobRepositoryInterface;
use Modules\Jobs\Models\JobOffer;

class ShowJobAction
{
    public function __construct(
        private readonly JobRepositoryInterface $repository,
    ) {}

    public function handle(JobOffer $job): JobOffer
    {
        return $this->repository->loadForShow($job);
    }
}
