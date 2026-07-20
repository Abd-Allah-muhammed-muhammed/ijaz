<?php

namespace Modules\Chat\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Contracts\ChatTypeHandlerInterface;
use Modules\Chat\Exceptions\ChatException;
use Modules\Chat\Models\Conversation;
use Throwable;

class OpenConversationAction
{
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

            $conversation = Conversation::query()->firstOrCreate(
                [
                    'operation_type' => $handler->operationType(),
                    'operation_id' => $operation->getKey(),
                ],
                [
                    'user1_id' => $user1->getKey(),
                    'user1_type' => $user1::class,
                    'user2_id' => $user2->getKey(),
                    'user2_type' => $user2::class,
                ],
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

            return $conversation->load(['user1', 'user2', 'lastMessage']);
        });
    }
}
