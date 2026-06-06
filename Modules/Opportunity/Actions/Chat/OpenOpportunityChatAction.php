<?php

namespace Modules\Opportunity\Actions\Chat;

use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Contracts\Repositories\ConversationRepositoryInterface;
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

            abort_if(
                $opportunity->acceptedOffer === null,
                422,
                __('opportunity.no_accepted_offer'),
            );

            return $this->conversationRepository->findOrCreateForOpportunity($opportunity);
        });
    }
}
