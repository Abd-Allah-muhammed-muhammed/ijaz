<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    protected $fillable = [
        'reviewer_type',
        'reviewer_id',
        'reviewee_type',
        'reviewee_id',
        'operation_type',
        'operation_id',
        'rating',
        'comment',
    ];

    public function reviewer(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewee(): MorphTo
    {
        return $this->morphTo();
    }

    public function operation(): MorphTo
    {
        return $this->morphTo();
    }
}
