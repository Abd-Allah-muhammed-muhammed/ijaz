<?php

namespace Modules\Chat\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Contracts\ChatTypeHandlerInterface;
use Modules\Chat\Contracts\Repositories\ConversationRepositoryInterface;
use Modules\Chat\Exceptions\ChatException;
use Modules\Chat\Models\Conversation;
use Throwable;

class OpenConversationAction
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversations,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        Model $actor,
        Model $operation,
        ChatTypeHandlerInterface $handler,
    ): Conversation {
        return DB::transaction(function () use ($actor, $operation, $handler) {
            if (! $handler->canOpen($actor, $operation)) {
                throw ChatException::notAllowed();
            }

            [$user1, $user2] = $handler->participants($operation);

            $conversation = $this->conversations->findOrCreateForOperation(
                $operation,
                $user1,
                $user2,
            );

            return $conversation->load(['user1', 'user2', 'lastMessage']);
        });
    }

    /**
     * @throws Throwable
     */
    public function handleMemberChat(Model $user1, Model $user2): Conversation
    {
        return DB::transaction(function () use ($user1, $user2) {
            $conversation = $this->conversations->findOrCreateMemberChat($user1, $user2);

            return $conversation->load(['user1', 'user2', 'lastMessage']);
        });
    }
}
