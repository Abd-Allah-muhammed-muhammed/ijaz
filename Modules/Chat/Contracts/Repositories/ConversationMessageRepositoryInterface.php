<?php

namespace Modules\Chat\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Chat\Contracts\HasConversation;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;

interface ConversationMessageRepositoryInterface
{
    public function createForConversation(
        Conversation $conversation,
        ?string $content,
        HasConversation $sender,
        HasConversation $receiver,
        ?Carbon $readAt,
        bool $hasAttachments,
    ): ConversationMessage;

    public function listForConversation(
        Conversation $conversation,
        int $perPage = 20,
        ?string $search = null,
    ): LengthAwarePaginator;

    /**
     * Newest-first take, then chronological (oldest → newest) for dashboard preview.
     *
     * @return Collection<int, ConversationMessage>
     */
    public function listRecentForConversation(
        Conversation $conversation,
        int $limit = 20,
    ): Collection;

    /**
     * Mark unread messages addressed to $reader as read.
     *
     * @return list<string> IDs of messages that were updated
     */
    public function markAsRead(
        Conversation $conversation,
        Model $reader,
    ): array;

    public function countUnreadFor(Model $receiver): int;
}
