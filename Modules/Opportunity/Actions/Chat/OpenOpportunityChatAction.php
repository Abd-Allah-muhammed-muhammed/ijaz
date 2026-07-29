<?php

namespace Modules\Opportunity\Actions\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Actions\OpenConversationAction;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Registry\ChatTypeRegistry;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Models\Opportunity;
use Throwable;

class OpenOpportunityChatAction
{
    public function __construct(
        private readonly OpenConversationAction $openConversationAction,
        private readonly ChatTypeRegistry $chatTypeRegistry,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Opportunity $opportunity, Model $actor): Conversation
    {
        return DB::transaction(function () use ($opportunity, $actor) {
            $opportunity->load(['author', 'acceptedOffer.author']);

            if ($opportunity->acceptedOffer === null) {
                throw new OpportunityException('opportunity.no_accepted_offer', 422);
            }

            return $this->openConversationAction->handle(
                $actor,
                $opportunity,
                $this->chatTypeRegistry->get(ChatTypeEnum::Opportunity),
            );
        });
    }
}
