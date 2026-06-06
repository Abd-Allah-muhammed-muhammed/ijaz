<?php

namespace Modules\Opportunity\Repositories;

use App\Models\Conversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Contracts\Repositories\ConversationRepositoryInterface;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Models\Opportunity;

class ConversationRepository implements ConversationRepositoryInterface
{
    public function findOrCreateForOpportunity(Opportunity $opportunity): Conversation
    {
        $opportunity->loadMissing(['author', 'acceptedOffer.author']);

        return Conversation::firstOrCreate(
            [
                'operation_type' => $opportunity::class,
                'operation_id' => $opportunity->id,
            ],
            [
                'user1_id' => $opportunity->author->getKey(),
                'user1_type' => $opportunity->author::class,
                'user2_id' => $opportunity->acceptedOffer->author->getKey(),
                'user2_type' => $opportunity->acceptedOffer->author::class,
            ],
        );
    }

    public function listForActor(Model $actor, int $perPage = 15): LengthAwarePaginator
    {
        return Conversation::query()
            ->where('operation_type', Opportunity::class)
            ->where(function ($query) use ($actor) {
                $query
                    ->where(function ($query) use ($actor) {
                        $query->where('user1_id', $actor->getKey())
                            ->where('user1_type', $actor::class);
                    })
                    ->orWhere(function ($query) use ($actor) {
                        $query->where('user2_id', $actor->getKey())
                            ->where('user2_type', $actor::class);
                    });
            })
            ->whereHas('operation', function ($query) {
                $query->whereNotIn('status', [
                    OpportunityStatusEnum::Ended->value,
                    OpportunityStatusEnum::Cancelled->value,
                ]);
            })
            ->with(['user1', 'user2', 'lastMassage', 'operation'])
            ->latest('last_message_at')
            ->paginate($perPage);
    }
}
