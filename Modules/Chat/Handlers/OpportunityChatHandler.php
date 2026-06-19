<?php

namespace Modules\Chat\Handlers;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Contracts\ChatTypeHandlerInterface;
use Modules\Chat\Support\ParticipantConversationMessenger;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Models\Opportunity;

class OpportunityChatHandler implements ChatTypeHandlerInterface
{
    public function operationType(): ?string
    {
        return Opportunity::class;
    }

    public function canOpen(Model $actor, Model $operation): bool
    {
        /** @var Opportunity $operation */
        if (
            $operation->author_type === $actor::class
            && (string) $operation->author_id === (string) $actor->getKey()
        ) {
            return true;
        }

        $operation->loadMissing('acceptedOffer');

        return $operation->acceptedOffer !== null
            && $operation->acceptedOffer->author_type === $actor::class
            && (string) $operation->acceptedOffer->author_id === (string) $actor->getKey();
    }

    public function participants(Model $operation): array
    {
        /** @var Opportunity $operation */
        return [$operation->author, $operation->acceptedOffer->author];
    }

    public function listQuery(Model $actor): Builder
    {
        return Conversation::query()
            ->where('operation_type', Opportunity::class)
            ->where(function (Builder $q) use ($actor) {
                $q->where(function ($q) use ($actor) {
                    $q->where('user1_type', $actor::class)
                        ->where('user1_id', $actor->getKey());
                })->orWhere(function ($q) use ($actor) {
                    $q->where('user2_type', $actor::class)
                        ->where('user2_id', $actor->getKey());
                });
            })
            ->whereHas('operation', function ($query) {
                $query->whereNotIn('status', [
                    OpportunityStatusEnum::Ended->value,
                    OpportunityStatusEnum::Cancelled->value,
                ]);
            })
            ->with(['user1', 'user2', 'lastMessage', 'operation'])
            ->latest('last_message_at');
    }

    public function messenger(Conversation $conversation): ParticipantConversationMessenger
    {
        return new ParticipantConversationMessenger($conversation);
    }
}
