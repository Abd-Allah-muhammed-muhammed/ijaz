<?php

namespace Modules\Chat\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Models\Conversation;

interface ConversationMessageRepositoryInterface
{
    public function listForConversation(
        Conversation $conversation,
        int $perPage = 20,
    ): LengthAwarePaginator;

    public function markAsRead(
        Conversation $conversation,
        Model $reader,
    ): void;
}
