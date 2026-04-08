<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BlockHistory extends Model
{
    protected $fillable = [
        'blocked_at',
        'blocked_until',
        'reason',
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
        'blocked_until' => 'datetime',
    ];

    public function authenticatable(): morphTo
    {
        return $this->morphTo();
    }
}
