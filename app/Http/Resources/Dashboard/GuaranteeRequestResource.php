<?php

namespace App\Http\Resources\Dashboard;

use App\Http\Resources\Api\V1\GuaranteeRequestUserResource;
use App\Http\Resources\Api\V1\MediaResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class GuaranteeRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'user_id' => $this->when(! $this->relationLoaded('user'), $this->user_id),
            'user_type' => $this->when(! $this->relationLoaded('user'), $this->user_type),
            'provider_id' => $this->when(! $this->relationLoaded('provider'), $this->provider_id),
            'provider_type' => $this->when(! $this->relationLoaded('provider'), $this->provider_type),
            'user' => new GuaranteeRequestUserResource($this->whenLoaded('user')),
            'provider' => new GuaranteeRequestUserResource($this->whenLoaded('provider')),
            'description' => $this->description,
            'amount' => $this->amount,
            'fees' => $this->fees,
            'total' => $this->total,
            'status' => $this->status->toArray(),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => $this->created_at,
        ];
    }
}
