<?php

namespace Modules\Chat\Http\Resources;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user1' => ChatUserResource::make($this->whenLoaded('user1')),
            'user2' => ChatUserResource::make($this->whenLoaded('user2')),
            'user' => ChatUserResource::make($this->whenLoaded('user')),
            'last_message' => ConversationMessageResource::make($this->whenLoaded('lastMassage')),
            'last_massage_at' => $this->last_message_at?->shortAbsoluteDiffForHumans(),
            'unread_count' => $this->when(isset($this->unread_messages_count), (int) $this->unread_messages_count),
        ];
    }
}
