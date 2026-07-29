<?php

namespace Modules\Chat\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Models\Conversation;

interface ChatTypeHandlerInterface
{
    /**
     * The morph class stored on conversations.operation_type (null for P2P member chat).
     */
    public function operationType(): ?string;

    /**
     * Can this actor open/access a conversation for the given operation?
     */
    public function canOpen(Model $actor, Model $operation): bool;

    /**
     * Return [user1, user2] for a new conversation.
     *
     * @return array<int, Model>
     */
    public function participants(Model $operation): array;

    /**
     * Base query for listing conversations for this actor + type.
     */
    public function listQuery(Model $actor): Builder;

    /**
     * Return the messenger instance for sending messages.
     */
    public function messenger(Conversation $conversation): object;

    /**
     * Load the operation model for this chat type by id.
     * Member (P2P) handlers should throw — they have no operation.
     */
    public function findOperation(int|string $id): Model;
}
