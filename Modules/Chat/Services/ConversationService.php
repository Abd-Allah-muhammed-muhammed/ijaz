<?php

namespace Modules\Chat\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Actions\ListConversationsAction;
use Modules\Chat\Actions\ListMessagesAction;
use Modules\Chat\Actions\OpenConversationAction;
use Modules\Chat\Actions\SendMessageAction;
use Modules\Chat\Contracts\ChatTypeHandlerInterface;
use Modules\Chat\Contracts\Repositories\ConversationMessageRepositoryInterface;
use Modules\Chat\Contracts\Repositories\ConversationRepositoryInterface;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;
use Modules\Chat\Registry\ChatTypeRegistry;

class ConversationService
{
    public function __construct(
        private readonly ChatTypeRegistry $registry,
        private readonly OpenConversationAction $openAction,
        private readonly ListConversationsAction $listAction,
        private readonly ListMessagesAction $listMessagesAction,
        private readonly SendMessageAction $sendAction,
        private readonly ConversationRepositoryInterface $conversations,
        private readonly ConversationMessageRepositoryInterface $messages,
    ) {}

    public function open(
        Model $actor,
        Model $operation,
        ChatTypeEnum $type,
    ): Conversation {
        $handler = $this->registry->get($type);

        return $this->openAction->handle($actor, $operation, $handler);
    }

    public function openMemberChat(Model $user1, Model $user2): Conversation
    {
        return $this->openAction->handleMemberChat($user1, $user2);
    }

    /**
     * System/admin bootstrap for an operation conversation.
     * Deliberately does NOT run canOpen() — distinct from actor-initiated open().
     */
    public function ensureForOperation(Model $operation, Model $user1, Model $user2): Conversation
    {
        return $this->conversations->findOrCreateForOperation($operation, $user1, $user2);
    }

    public function list(
        Model $actor,
        ChatTypeEnum $type,
        int $perPage = 15,
    ): LengthAwarePaginator {
        $handler = $this->registry->get($type);

        return $this->listAction->handle($actor, $handler, $perPage);
    }

    public function messages(
        Conversation $conversation,
        Model $actor,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->listMessagesAction->handle($conversation, $actor, $perPage);
    }

    public function send(
        Conversation $conversation,
        Model $actor,
        ChatMessageData $data,
        ChatTypeEnum $type,
    ): ConversationMessage {
        $handler = $this->registry->get($type);

        return $this->sendAction->handle($conversation, $actor, $data, $handler);
    }

    public function getHandler(ChatTypeEnum $type): ChatTypeHandlerInterface
    {
        return $this->registry->get($type);
    }

    /**
     * Provider dashboard listing of order conversations, excluding an operation status via join.
     */
    public function listForProviderOrderOperations(
        Model $provider,
        string $operationType,
        string $operationsTable,
        string|int $excludedOperationStatus,
        int $perPage = 10,
    ): LengthAwarePaginator {
        return $this->conversations->paginateForProviderOrderOperations(
            $provider,
            $operationType,
            $operationsTable,
            $excludedOperationStatus,
            $perPage,
        );
    }

    /**
     * Unread message count across all conversations where the actor is the receiver.
     */
    public function countUnreadFor(Model $actor): int
    {
        return $this->messages->countUnreadFor($actor);
    }
}
