<?php

namespace App\Traits;

use App\Models\JobOffer;
use App\Models\Review;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property Collection<JobOffer> $jobs
 *
 * @mixin Model
 */
trait HasReviews
{
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewee');
    }

    public function opinions(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewer');
    }
}
