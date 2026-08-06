<?php

namespace Modules\Chat\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Chat\Contracts\HasConversation;
use Modules\Chat\Contracts\Repositories\ConversationMessageRepositoryInterface;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;

class ConversationMessageRepository implements ConversationMessageRepositoryInterface
{
    public function createForConversation(
        Conversation $conversation,
        ?string $content,
        HasConversation $sender,
        HasConversation $receiver,
        ?Carbon $readAt,
        bool $hasAttachments,
    ): ConversationMessage {
        return $conversation
            ->messages()
            ->create([
                'content' => $content,
                'sender_id' => $sender->getKey(),
                'sender_type' => get_class($sender),
                'read_at' => $readAt,
                'receiver_id' => $receiver->getKey(),
                'receiver_type' => get_class($receiver),
                'has_attachments' => $hasAttachments,
            ])
            ->setRelation('sender', $sender)
            ->setRelation('receiver', $receiver);
    }

    public function listForConversation(
        Conversation $conversation,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $conversation->messages()
            ->with(['sender', 'receiver', 'media', 'attachments'])
            ->latest()
            ->paginate($perPage);
    }

    public function listRecentForConversation(
        Conversation $conversation,
        int $limit = 20,
    ): Collection {
        return $conversation->messages()
            ->with(['media', 'sender', 'attachments'])
            ->latest()
            ->take($limit)
            ->get()
            ->reverse()
            ->values();
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

    public function countUnreadFor(Model $receiver): int
    {
        return ConversationMessage::query()
            ->whereMorphedTo('receiver', $receiver)
            ->whereNull('read_at')
            ->count();
    }
}
