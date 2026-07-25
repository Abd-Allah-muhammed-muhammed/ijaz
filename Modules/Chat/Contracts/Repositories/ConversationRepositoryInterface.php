<?php

namespace Modules\Chat\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Models\Conversation;

interface ConversationRepositoryInterface
{
    public function findOrCreate(
        ?Model $operation,
        Model $user1,
        Model $user2,
    ): Conversation;

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
