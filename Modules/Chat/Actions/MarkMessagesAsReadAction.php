<?php

namespace Modules\Chat\Actions;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Contracts\Repositories\ConversationMessageRepositoryInterface;
use Modules\Chat\Infrastructure\Events\MessagesReadEvent;
use Modules\Chat\Models\Conversation;

/**
 * Option C: only the designated 2-party receiver marks messages read.
 * Admins are oversight — never drive read_at / checkmarks.
 */
class MarkMessagesAsReadAction
{
    public function __construct(
        private readonly ConversationMessageRepositoryInterface $messageRepository,
    ) {}

    /**
     * @return list<string> Message IDs that were newly marked read
     */
    public function handle(Conversation $conversation, Model $reader): array
    {
        if ($reader instanceof Admin) {
            return [];
        }

        $messageIds = $this->messageRepository->markAsRead($conversation, $reader);

        if ($messageIds === []) {
            return [];
        }

        $readAt = now()->toIso8601String();

        broadcast(new MessagesReadEvent(
            (string) $conversation->id,
            $messageIds,
            $readAt,
        ))->toOthers();

        return $messageIds;
    }
}
