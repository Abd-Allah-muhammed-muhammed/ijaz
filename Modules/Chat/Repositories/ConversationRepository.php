<?php

namespace Modules\Chat\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Contracts\Repositories\ConversationRepositoryInterface;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;

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

    public function findOrCreateForOperation(
        Model $operation,
        Model $user1,
        Model $user2,
    ): Conversation {
        return Conversation::query()->firstOrCreate([
            'operation_type' => $operation::class,
            'operation_id' => $operation->getKey(),
        ], [
            'user1_type' => $user1::class,
            'user1_id' => $user1->getKey(),
            'user2_type' => $user2::class,
            'user2_id' => $user2->getKey(),
        ]);
    }

    public function findOrCreateMemberChat(Model $user1, Model $user2): Conversation
    {
        $conversation = Conversation::query()
            ->whereNull('operation_type')
            ->where(function (Builder $query) use ($user1, $user2) {
                $query->where(function (Builder $query) use ($user1, $user2) {
                    $query->where('user1_type', $user1::class)
                        ->where('user1_id', $user1->getKey())
                        ->where('user2_type', $user2::class)
                        ->where('user2_id', $user2->getKey());
                })->orWhere(function (Builder $query) use ($user1, $user2) {
                    $query->where('user1_type', $user2::class)
                        ->where('user1_id', $user2->getKey())
                        ->where('user2_type', $user1::class)
                        ->where('user2_id', $user1->getKey());
                });
            })
            ->first();

        if (! $conversation) {
            $conversation = Conversation::query()->create([
                'user1_type' => $user1::class,
                'user1_id' => $user1->getKey(),
                'user2_type' => $user2::class,
                'user2_id' => $user2->getKey(),
            ]);
        }

        return $conversation;
    }

    public function touchLastMessage(
        Conversation $conversation,
        ConversationMessage $lastMessage,
    ): void {
        $conversation->update([
            'last_message_at' => $lastMessage->created_at,
            'last_message_id' => $lastMessage->id,
        ]);
        $conversation->setRelation('lastMessage', $lastMessage);
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

    public function paginateForProviderOrderOperations(
        Model $provider,
        string $operationType,
        string $operationsTable,
        string|int $excludedOperationStatus,
        int $perPage = 10,
    ): LengthAwarePaginator {
        return Conversation::query()
            ->select('conversations.*')
            ->where('operation_type', $operationType)
            ->join($operationsTable, function ($join) use ($operationsTable, $excludedOperationStatus) {
                $join->on("{$operationsTable}.id", 'conversations.operation_id')
                    ->where("{$operationsTable}.status", '!=', $excludedOperationStatus);
            })
            ->with(['lastMessage.sender', 'lastMessage.lastAttachment', 'user2', 'user1'])
            ->withCountUnreadMessagesFor($provider)
            ->where(function (Builder $query) use ($provider) {
                $query->whereMorphedTo('user1', $provider)
                    ->orWhereMorphedTo('user2', $provider);
            })
            ->paginate($perPage)
            ->withQueryString();
    }
}
