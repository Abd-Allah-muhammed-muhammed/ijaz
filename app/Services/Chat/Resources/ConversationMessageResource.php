<?php

namespace App\Services\Chat\Resources;

use App\Models\ConversationMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ConversationMessage
 */
class ConversationMessageResource extends JsonResource
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
            'conversation_id' => $this->conversation_id,
            'content' => $this->content,
            'sender' => UserResource::make($this->whenLoaded('sender')),
            'attachments' => ConversationAttachmentResource::collection($this->whenLoaded('attachments')),
            'last_attachment' => ConversationAttachmentResource::make($this->whenLoaded('lastAttachment')),
            'read_at' => $this->read_at,
            'created_at' => $this->created_at?->shortAbsoluteDiffForHumans() ?: '',
        ];
    }
}
