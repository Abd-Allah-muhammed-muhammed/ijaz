<?php

namespace App\Traits;

use App\Models\JobOffer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property Collection<int, JobOffer> $jobs
 *
 * @mixin Model
 */
trait HasJobs
{
    public function jobs(): MorphMany
    {
        return $this->morphMany(JobOffer::class, 'user');
    }
}
