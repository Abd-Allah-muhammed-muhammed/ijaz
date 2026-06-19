<?php

namespace Modules\Chat\Repositories;

use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Contracts\Repositories\ConversationRepositoryInterface;

class ConversationRepository implements ConversationRepositoryInterface
{
    public function findOrCreate(
        ?Model $operation,
        Model $user1,
        Model $user2,
    ): Conversation {
        $where = [
            'user1_id' => $user1->getKey(),
            'user1_type' => $user1::class,
            'user2_id' => $user2->getKey(),
            'user2_type' => $user2::class,
        ];

        if ($operation) {
            $where['operation_type'] = $operation::class;
            $where['operation_id'] = $operation->getKey();
        }

        return Conversation::firstOrCreate($where);
    }

    public function findById(string $id): Conversation
    {
        return Conversation::with([
            'user1', 'user2', 'lastMessage', 'operation',
        ])->findOrFail($id);
    }

    public function listForActor(
        Model $actor,
        ?string $operationType,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return Conversation::query()
            ->when(
                $operationType,
                fn ($q) => $q->where('operation_type', $operationType),
                fn ($q) => $q->whereNull('operation_type'),
            )
            ->where(function ($q) use ($actor) {
                $q->where(function ($q) use ($actor) {
                    $q->where('user1_type', $actor::class)
                        ->where('user1_id', $actor->getKey());
                })->orWhere(function ($q) use ($actor) {
                    $q->where('user2_type', $actor::class)
                        ->where('user2_id', $actor->getKey());
                });
            })
            ->with(['user1', 'user2', 'lastMessage', 'operation'])
            ->latest('last_message_at')
            ->paginate($perPage);
    }
}
