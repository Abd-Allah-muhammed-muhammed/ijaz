<?php

namespace Modules\Chat\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Contracts\Repositories\ConversationMessageRepositoryInterface;
use Modules\Chat\Models\Conversation;

class ListMessagesAction
{
    public function __construct(
        private readonly ConversationMessageRepositoryInterface $messageRepository,
    ) {}

    public function handle(
        Conversation $conversation,
        Model $actor,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $this->messageRepository->markAsRead($conversation, $actor);

        return $this->messageRepository->listForConversation($conversation, $perPage);
    }
}
