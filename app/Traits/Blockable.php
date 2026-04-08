<?php

namespace App\Traits;

use App\Models\BlockHistory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait Blockable
{
    public function blockHistories(): MorphMany
    {
        return $this->morphMany(BlockHistory::class, 'authenticatable');
    }

    public function latestBlockHistory(): MorphOne
    {
        return $this->morphOne(BlockHistory::class, 'authenticatable')->latestOfMany();
    }

    public function block(int $blocked_days = 0, ?string $reason = null)
    {
        $now = now();
        $this->update([
            'blocked_at' => $now,
            'blocked_until' => $blocked_days ? $now->addDays($blocked_days) : null,
        ]);

        $this->blockHistories()->create([
            'blocked_at' => $this->blocked_at,
            'blocked_until' => $this->blocked_until,
            'reason' => $reason,
        ]);
    }
}
