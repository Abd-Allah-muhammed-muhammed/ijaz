<?php

namespace Modules\Jobs\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Modules\Jobs\Exceptions\JobsException;
use Modules\Jobs\Models\JobOffer;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait AuthorizesJobRequests
{
    /**
     * @throws JobsException
     */
    protected function ensureJobOwnedBy(JobOffer $job, Model $actor): void
    {
        if ($job->user()->isNot($actor)) {
            throw new JobsException('not_found', 404);
        }
    }

    /**
     * @throws JobsException
     */
    protected function ensureMediaBelongsToJob(JobOffer $job, Media $media): void
    {
        if ($media->model()->isNot($job)) {
            throw new JobsException('media not found', 404);
        }
    }
}
