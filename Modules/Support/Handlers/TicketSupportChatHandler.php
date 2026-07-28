<?php

namespace Modules\Support\Handlers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Contracts\ChatTypeHandlerInterface;
use Modules\Chat\Contracts\Repositories\SystemRepositoryInterface;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Support\ParticipantConversationMessenger;
use Modules\Support\Models\TicketSupport;

class TicketSupportChatHandler implements ChatTypeHandlerInterface
{
    public function __construct(
        private readonly SystemRepositoryInterface $systemRepository,
    ) {}

    public function operationType(): ?string
    {
        return TicketSupport::class;
    }

    public function canOpen(Model $actor, Model $operation): bool
    {
        /** @var TicketSupport $operation */
        return $operation->user_id === $actor->getKey()
            && $operation->user_type === $actor::class;
    }

    public function participants(Model $operation): array
    {
        /** @var TicketSupport $operation */
        // Previously System::first() (null if missing). findOrCreateDefault matches the
        // Service/SupportChat singleton semantics and avoids a null participant.
        return [$this->systemRepository->findOrCreateDefault(), $operation->user];
    }

    public function listQuery(Model $actor): Builder
    {
        return Conversation::query()
            ->where('operation_type', TicketSupport::class)
            ->where(function (Builder $q) use ($actor) {
                $q->where(function ($q) use ($actor) {
                    $q->where('user1_type', $actor::class)
                        ->where('user1_id', $actor->getKey());
                })->orWhere(function ($q) use ($actor) {
                    $q->where('user2_type', $actor::class)
                        ->where('user2_id', $actor->getKey());
                });
            })
            ->with(['user1', 'user2', 'lastMessage', 'operation'])
            ->latest('last_message_at');
    }

    public function messenger(Conversation $conversation): ParticipantConversationMessenger
    {
        return new ParticipantConversationMessenger($conversation);
    }

    public function findOperation(int|string $id): Model
    {
        return TicketSupport::query()->findOrFail($id);
    }
}
