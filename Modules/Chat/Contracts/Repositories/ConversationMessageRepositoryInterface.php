<?php

namespace Modules\Chat\Contracts\Repositories;

use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

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
