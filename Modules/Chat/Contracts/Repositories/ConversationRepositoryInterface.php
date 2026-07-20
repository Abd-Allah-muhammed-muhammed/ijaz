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
}
