<?php

namespace Modules\Reviews\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Reviews\Models\Review;

trait Reviewable
{
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'operation');
    }
}
