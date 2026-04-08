<?php

namespace App\Http\Resources\Api\V1;

use App\Models\TicketSupport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TicketSupport */
class TicketSupportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'status' => $this->status?->toArray(),
            'user_id' => $this->when(! $this->relationLoaded('user'), $this->user_id),
            'user_type' => $this->when(! $this->relationLoaded('user'), $this->user_type),
            'operation_id' => $this->when(! $this->relationLoaded('operation'), $this->operation_id),
            'operation_type' => $this->when(! $this->relationLoaded('operation'), $this->operation_type),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
