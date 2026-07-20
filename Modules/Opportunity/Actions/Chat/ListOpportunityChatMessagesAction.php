<?php

namespace Modules\Opportunity\Actions\Chat;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Actions\ListMessagesAction;
use Modules\Chat\Models\Conversation;

class ListOpportunityChatMessagesAction
{
    public function __construct(
        private readonly ListMessagesAction $listMessagesAction,
    ) {}

    public function handle(Conversation $conversation, Model $actor, int $perPage = 20): LengthAwarePaginator
    {
        return $this->listMessagesAction->handle($conversation, $actor, $perPage);
    }
}
