<?php

namespace Modules\Opportunity\Actions\Chat;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Actions\ListConversationsAction;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Registry\ChatTypeRegistry;

class ListOpportunityChatsAction
{
    public function __construct(
        private readonly ListConversationsAction $listConversationsAction,
        private readonly ChatTypeRegistry $chatTypeRegistry,
    ) {}

    public function handle(Model $actor, int $perPage = 15): LengthAwarePaginator
    {
        return $this->listConversationsAction->handle(
            $actor,
            $this->chatTypeRegistry->get(ChatTypeEnum::Opportunity),
            $perPage,
        );
    }
}
