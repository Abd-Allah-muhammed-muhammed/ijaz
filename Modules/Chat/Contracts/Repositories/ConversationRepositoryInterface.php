<?php

namespace Modules\Chat\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;

interface ConversationRepositoryInterface
{
    public function findOrCreate(
        ?Model $operation,
        Model $user1,
        Model $user2,
    ): Conversation;

    /**
     * System/admin bootstrap path: keyed by operation only (does not run canOpen).
     */
    public function findOrCreateForOperation(
        Model $operation,
        Model $user1,
        Model $user2,
    ): Conversation;

    /**
     * Member (P2P) chat: null operation_type, bidirectional user1/user2 match.
     */
    public function findOrCreateMemberChat(Model $user1, Model $user2): Conversation;

    public function touchLastMessage(
        Conversation $conversation,
        ConversationMessage $lastMessage,
    ): void;

    public function findById(string $id): Conversation;

    public function listForActor(
        Model $actor,
        ?string $operationType,
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Provider dashboard order-chat listing: active order conversations for a provider.
     *
     * @param  string|int  $excludedOperationStatus  Status value excluded via join on $operationsTable
     */
    public function paginateForProviderOrderOperations(
        Model $provider,
        string $operationType,
        string $operationsTable,
        string|int $excludedOperationStatus,
        int $perPage = 10,
    ): LengthAwarePaginator;
}
