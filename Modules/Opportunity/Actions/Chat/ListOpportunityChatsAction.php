<?php

namespace Modules\Opportunity\Actions\Chat;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Opportunity\Contracts\Repositories\ConversationRepositoryInterface;

class ListOpportunityChatsAction
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepository,
    ) {}

    public function handle(Model $actor, int $perPage = 15): LengthAwarePaginator
    {
        return $this->conversationRepository->listForActor($actor, $perPage);
    }
}
