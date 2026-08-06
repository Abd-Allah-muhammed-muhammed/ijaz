<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Modules\Chat\Models\ConversationAttachment;
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
        $attachmentsLoaded = $this->relationLoaded('media') || $this->relationLoaded('attachments');
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
            'created_at' => $this->created_at?->shortAbsoluteDiffForHumans() ?: '',
        ];
    }

    /**
     * Merge MediaLibrary + unmigrated legacy rows; mark missing files unavailable.
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
        $migratedLegacyIds = [];

        if ($this->relationLoaded('media')) {
            foreach ($this->getMedia('attachments') as $media) {
                /** @var Media $media */
                $legacyId = $media->getCustomProperty('legacy_attachment_id');
                if (is_string($legacyId) && $legacyId !== '') {
                    $migratedLegacyIds[] = $legacyId;
                }

                $items[] = ConversationAttachmentResource::make($media)->resolve();
            }
        }

        if ($this->relationLoaded('attachments')) {
            /** @var Collection<int, ConversationAttachment> $legacy */
            $legacy = $this->attachments;
            foreach ($legacy as $attachment) {
                if (in_array($attachment->id, $migratedLegacyIds, true)) {
                    continue;
                }

                $items[] = ConversationAttachmentResource::make($attachment)->resolve();
            }
        }

        // Attachment-only messages whose files never made it into MediaLibrary
        // (and have no remaining legacy rows) would otherwise render as blank bubbles.
        if ($items === [] && (bool) $this->has_attachments) {
            $items[] = ConversationAttachmentResource::unavailablePlaceholder(
                'unavailable-'.$this->id,
            );
        }

        return $this->resolvedAttachments = $items;
    }
}
