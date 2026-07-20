<?php

namespace Modules\Chat\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Contracts\Repositories\ConversationMessageRepositoryInterface;
use Modules\Chat\Models\Conversation;

class ConversationMessageRepository implements ConversationMessageRepositoryInterface
{
    public function listForConversation(
        Conversation $conversation,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $conversation->messages()
            ->with(['sender', 'receiver', 'attachments'])
            ->latest()
            ->paginate($perPage);
    }

    public function markAsRead(
        Conversation $conversation,
        Model $reader,
    ): void {
        $conversation->messages()
            ->where('receiver_type', $reader::class)
            ->where('receiver_id', $reader->getKey())
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'read_by_id' => $reader->getKey(),
                'read_by_type' => $reader::class,
            ]);
    }
}
