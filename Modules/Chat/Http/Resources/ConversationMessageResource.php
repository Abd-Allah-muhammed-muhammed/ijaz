<?php

namespace Modules\Chat\Http\Resources;

use App\Http\Resources\Api\V1\MediaResource;
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
            // Key name stays `attachments` for API freeze; payload is MediaResource shape.
            'attachments' => $this->when(
                $this->relationLoaded('media'),
                fn () => MediaResource::collection($this->getMedia('attachments')),
            ),
            // Only present when a last media item exists (mirrors former whenLoaded('lastAttachment')).
            'last_attachment' => $this->when(
                $this->relationLoaded('media') && $this->lastMediaAttachment() !== null,
                fn () => MediaResource::make($this->lastMediaAttachment()),
            ),
            'read_at' => $this->read_at,
            'created_at' => $this->created_at?->shortAbsoluteDiffForHumans() ?: '',
        ];
    }
}
