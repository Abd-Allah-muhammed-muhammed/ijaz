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

    public function markAsRead(
        Conversation $conversation,
        Model $reader,
    ): void;

    public function countUnreadFor(Model $receiver): int;
}
