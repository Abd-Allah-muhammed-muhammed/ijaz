<?php

namespace Modules\Opportunity\Services;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Services\Chat\Requests\SendMessageRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Actions\Chat\ListOpportunityChatMessagesAction;
use Modules\Opportunity\Actions\Chat\ListOpportunityChatsAction;
use Modules\Opportunity\Actions\Chat\OpenOpportunityChatAction;
use Modules\Opportunity\Actions\Chat\SendOpportunityChatMessageAction;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\Models\Opportunity;
use Throwable;

class OpportunityChatService
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $opportunities,
        private readonly OpenOpportunityChatAction $openAction,
        private readonly ListOpportunityChatsAction $listAction,
        private readonly ListOpportunityChatMessagesAction $listMessagesAction,
        private readonly SendOpportunityChatMessageAction $sendAction,
    ) {}

    public function resolveOpportunity(string $opportunityId): Opportunity
    {
        return $this->opportunities->findById($opportunityId);
    }

    /**
     * @throws Throwable
     */
    public function open(Opportunity $opportunity): Conversation
    {
        return $this->openAction->handle($opportunity);
    }

    public function listForActor(Model $actor, int $perPage = 15): LengthAwarePaginator
    {
        return $this->listAction->handle($actor, $perPage);
    }

    public function listMessages(Conversation $conversation, Model $actor, int $perPage = 20): LengthAwarePaginator
    {
        return $this->listMessagesAction->handle($conversation, $actor, $perPage);
    }

    /**
     * @throws Throwable
     */
    public function sendMessage(Conversation $conversation, Model $sender, SendMessageRequest $request): ConversationMessage
    {
        return $this->sendAction->handle(
            $conversation,
            $sender,
            $request->input('content'),
            $request->file('files', []),
        );
    }
}
