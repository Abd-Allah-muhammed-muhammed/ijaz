<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Chat\Models\ConversationMessage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @mixin ConversationMessage
 */
class ConversationMessageResource extends JsonResource
{
    /** @var list<array<string, mixed>>|null */
    private ?array $resolvedAttachments = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attachmentsLoaded = $this->relationLoaded('media');
        $attachments = $attachmentsLoaded ? $this->resolveAttachments() : null;
        $lastAttachment = is_array($attachments) && $attachments !== []
            ? $attachments[array_key_last($attachments)]
            : null;

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'content' => $this->content,
            'sender' => ChatUserResource::make($this->whenLoaded('sender')),
            // Key name stays `attachments` for API freeze; payload is MediaResource
            // shape + `available` (false when the file is missing on disk).
            'attachments' => $this->when($attachmentsLoaded, $attachments),
            // Only present when at least one attachment payload exists.
            'last_attachment' => $this->when($lastAttachment !== null, $lastAttachment),
            'read_at' => $this->read_at,
            // Humanized for display fallback / API freeze; ISO enables live client-side ticks.
            'created_at' => $this->created_at?->shortAbsoluteDiffForHumans() ?: '',
            'created_at_iso' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * MediaLibrary attachments only; mark missing files unavailable.
     *
     * @return list<array<string, mixed>>
     */
    private function resolveAttachments(): array
    {
        if ($this->resolvedAttachments !== null) {
            return $this->resolvedAttachments;
        }

        /** @var list<array<string, mixed>> $items */
        $items = [];

        foreach ($this->getMedia('attachments') as $media) {
            /** @var Media $media */
            $items[] = ConversationAttachmentResource::make($media)->resolve();
        }

        // Attachment-flagged messages with no media rows would otherwise render blank.
        if ($items === [] && (bool) $this->has_attachments) {
            $items[] = ConversationAttachmentResource::unavailablePlaceholder(
                'unavailable-'.$this->id,
            );
        }

        return $this->resolvedAttachments = $items;
    }
}
