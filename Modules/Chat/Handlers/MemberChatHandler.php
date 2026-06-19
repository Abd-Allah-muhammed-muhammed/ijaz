<?php

namespace Modules\Chat\Handlers;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Contracts\ChatTypeHandlerInterface;
use Modules\Chat\Support\ParticipantConversationMessenger;

class MemberChatHandler implements ChatTypeHandlerInterface
{
    public function operationType(): ?string
    {
        return null;
    }

    public function canOpen(Model $actor, Model $operation): bool
    {
        return true;
    }

    public function participants(Model $operation): array
    {
        return [];
    }

    public function listQuery(Model $actor): Builder
    {
        return Conversation::query()
            ->whereNull('operation_type')
            ->where(function (Builder $q) use ($actor) {
                $q->where(function ($q) use ($actor) {
                    $q->where('user1_type', $actor::class)
                        ->where('user1_id', $actor->getKey());
                })->orWhere(function ($q) use ($actor) {
                    $q->where('user2_type', $actor::class)
                        ->where('user2_id', $actor->getKey());
                });
            })
            ->with(['user1', 'user2', 'lastMessage'])
            ->latest('last_message_at');
    }

    public function messenger(Conversation $conversation): ParticipantConversationMessenger
    {
        return new ParticipantConversationMessenger($conversation);
    }
}
