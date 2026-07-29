<?php

namespace Modules\Jobs\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Jobs\Contracts\Repositories\JobRepositoryInterface;
use Modules\Jobs\Exceptions\JobsException;
use Modules\Jobs\Http\Controllers\Concerns\AuthorizesJobRequests;
use Modules\Jobs\Models\JobOffer;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DeleteJobMediaAction
{
    use AuthorizesJobRequests;

    public function __construct(
        private readonly JobRepositoryInterface $repository,
    ) {}

    /**
     * @throws JobsException
     */
    public function handle(JobOffer $job, Media $media, Model $actor): void
    {
        $this->ensureJobOwnedBy($job, $actor);
        $this->ensureMediaBelongsToJob($job, $media);
        $this->repository->deleteMedia($job, $media);
    }
}
