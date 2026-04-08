<?php

namespace App\Services\Chat\Resources;

use App\Services\Chat\Contracts\HasConversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin  HasConversation
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
            'id' => $this->getKey(),
            'socket_id' => $this->getAuthIdentifierForBroadcasting(),
            'type' => $this->getType(),
            'name' => $this->name,
            'online' => (bool) $this->online ?? false,
            'image' => $this->getImageUrl(),
        ];
    }
}
