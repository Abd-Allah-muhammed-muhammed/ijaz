<?php

namespace Modules\Orders\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\OrderStatusHistory;

/** @mixin OrderStatusHistory */
class StatusHistoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fromStatus = $this->from_status !== null
            ? OrderStatusEnum::tryFrom($this->from_status)?->toArray()
            : null;

        return [
            'id' => $this->id,
            'from_status' => $fromStatus,
            'to_status' => $this->status->toArray(),
            'reason' => $this->reason,
            'notes' => $this->notes,
            'actor' => $this->when($this->relationLoaded('actor') && $this->actor !== null, fn () => [
                'id' => $this->actor->getKey(),
                'name' => $this->actor_name ?? $this->actor->name ?? null,
                'type' => class_basename($this->actor),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
