<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Provider */
class BlockHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'blocked_at' => $this->blocked_at?->toDateTimeString(),
            'blocked_until' => $this->blocked_until?->toDateTimeString(),
            'reason' => $this->reason,
        ];
    }
}
