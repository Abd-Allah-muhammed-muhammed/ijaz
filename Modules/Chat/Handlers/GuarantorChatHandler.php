<?php

namespace Modules\Chat\Handlers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Contracts\ChatTypeHandlerInterface;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Support\ParticipantConversationMessenger;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Models\GuarantorRequest;

class GuarantorChatHandler implements ChatTypeHandlerInterface
{
    public function operationType(): ?string
    {
        return GuarantorRequest::class;
    }

    public function canOpen(Model $actor, Model $operation): bool
    {
        /** @var GuarantorRequest $operation */
        return (
            $operation->requester_type === $actor::class
            && (string) $operation->requester_id === (string) $actor->getKey()
        ) || (
            $operation->counterparty_type === $actor::class
            && (string) $operation->counterparty_id === (string) $actor->getKey()
        );
    }

    public function participants(Model $operation): array
    {
        /** @var GuarantorRequest $operation */
        return [$operation->requester, $operation->counterparty];
    }

    public function listQuery(Model $actor): Builder
    {
        return Conversation::query()
            ->where('operation_type', GuarantorRequest::class)
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
                    GuarantorStatusEnum::RejectedByAdmin->value,
                    GuarantorStatusEnum::Rejected->value,
                    GuarantorStatusEnum::Ended->value,
                    GuarantorStatusEnum::Cancelled->value,
                    GuarantorStatusEnum::Refunded->value,
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
