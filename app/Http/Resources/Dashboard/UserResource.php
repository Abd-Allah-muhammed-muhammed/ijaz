<?php

namespace App\Http\Resources\Dashboard;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @see User
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'socket_id' => $this->getAuthIdentifierForBroadcasting(),
            'name' => $this->name,
            'f_name' => $this->f_name,
            'l_name' => $this->l_name,
            'phone' => $this->phone,
            'image' => $this->image_url,
            'language' => $this->language,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'email' => $this->email,
            'nationality_id' => $this->nationality_id,
            'nationality' => NationalityResource::make($this->whenLoaded('nationality')),
            'status' => $this->status->toArray(),
            'blocked_at' => $this->blocked_at?->toDateTimeString(),
            'blocked_until' => $this->blocked_until?->toDateTimeString(),
            'latest_block_history' => BlockHistoryResource::make($this->whenLoaded('latestBlockHistory')),
            'orders_count' => $this->whenCounted('orders', $this->orders_count),
            'created_at' => $this->created_at,
        ];
    }
}
