<?php

namespace App\Traits;

use App\Models\JobOffer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @property Collection<JobOffer> $jobs
 *
 * @mixin Model
 */
trait HasJobs
{
    public function jobs(): MorphOne
    {
        return $this->morphOne(JobOffer::class, 'user');
    }
}
