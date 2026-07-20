<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Chat\Models\ConversationMessage;

/**
 * @mixin ConversationMessage
 */
class ConversationMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'content' => $this->content,
            'sender' => ChatUserResource::make($this->whenLoaded('sender')),
            'attachments' => ConversationAttachmentResource::collection($this->whenLoaded('attachments')),
            'last_attachment' => ConversationAttachmentResource::make($this->whenLoaded('lastAttachment')),
            'read_at' => $this->read_at,
            'created_at' => $this->created_at?->shortAbsoluteDiffForHumans() ?: '',
        ];
    }
}
