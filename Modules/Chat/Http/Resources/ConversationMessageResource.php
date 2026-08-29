<?php

namespace Modules\Chat\Http\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Chat\Contracts\ResolvesMessagePartyRole;
use Modules\Chat\Models\ConversationMessage;
use Modules\Chat\Registry\ChatTypeRegistry;
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
            // Guarantor-only (and any future ResolvesMessagePartyRole handler).
            // Absent for Orders/Member/etc. so their frozen key sets stay unchanged.
            'party_role' => $this->when(
                $this->partyRoleHandler() !== null,
                fn () => $this->resolvePartyRole(),
            ),
            'read_at' => $this->read_at,
            // Humanized for display fallback / API freeze; ISO enables live client-side ticks.
            'created_at' => $this->created_at?->shortAbsoluteDiffForHumans() ?: '',
            'created_at_iso' => $this->created_at?->toIso8601String(),
        ];
    }

    private function partyRoleHandler(): ?ResolvesMessagePartyRole
    {
        // Only when the caller eager-loaded conversation (Guarantor dashboard/API).
        // Avoids N+1 and keeps party_role absent for Orders/Member/etc.
        if (! $this->relationLoaded('conversation') || $this->conversation === null) {
            return null;
        }

        $operationType = $this->conversation->operation_type;

        if (! is_string($operationType) || $operationType === '') {
            return null;
        }

        $handler = app(ChatTypeRegistry::class)->getByOperationType($operationType);

        return $handler instanceof ResolvesMessagePartyRole ? $handler : null;
    }

    /**
     * @return 'requester'|'counterparty'|null
     */
    private function resolvePartyRole(): ?string
    {
        $handler = $this->partyRoleHandler();

        if ($handler === null) {
            return null;
        }

        $this->loadMissing(['conversation.operation', 'sender']);

        $operation = $this->conversation?->operation;
        $sender = $this->sender;

        if (! $operation instanceof Model || ! $sender instanceof Model) {
            return null;
        }

        return $handler->resolvePartyRole($sender, $operation);
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
