<?php

namespace Modules\Chat\Contracts;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface ChatTypeHandlerInterface
{
    /**
     * The operation_type value stored in conversations table.
     * e.g. App\Models\Order::class
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
}
