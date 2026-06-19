<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Chat\Contracts\HasConversation;

/**
 * @mixin HasConversation
 */
class ChatUserResource extends JsonResource
{
    /**
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
