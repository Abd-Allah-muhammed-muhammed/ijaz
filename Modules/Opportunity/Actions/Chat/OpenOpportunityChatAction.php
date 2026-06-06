<?php

namespace Modules\Opportunity\Actions\Chat;

use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Contracts\Repositories\ConversationRepositoryInterface;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Models\Opportunity;
use Throwable;

class OpenOpportunityChatAction
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Opportunity $opportunity): Conversation
    {
        return DB::transaction(function () use ($opportunity) {
            $opportunity->load(['author', 'acceptedOffer.author']);

            if ($opportunity->acceptedOffer === null) {
                throw new OpportunityException('opportunity.no_accepted_offer', 422);
            }

            return $this->conversationRepository->findOrCreateForOpportunity($opportunity);
        });
    }
}
