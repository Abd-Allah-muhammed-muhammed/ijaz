<?php

namespace Modules\Chat\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Actions\ListConversationsAction;
use Modules\Chat\Actions\ListMessagesAction;
use Modules\Chat\Actions\OpenConversationAction;
use Modules\Chat\Actions\SendMessageAction;
use Modules\Chat\Contracts\ChatTypeHandlerInterface;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;
use Modules\Chat\Registry\ChatTypeRegistry;

class ConversationService
{
    public function __construct(
        private readonly ChatTypeRegistry $registry,
        private readonly OpenConversationAction $openAction,
        private readonly ListConversationsAction $listAction,
        private readonly ListMessagesAction $listMessagesAction,
        private readonly SendMessageAction $sendAction,
    ) {}

    public function open(
        Model $actor,
        Model $operation,
        ChatTypeEnum $type,
    ): Conversation {
        $handler = $this->registry->get($type);

        return $this->openAction->handle($actor, $operation, $handler);
    }

    public function openMemberChat(Model $user1, Model $user2): Conversation
    {
        return $this->openAction->handleMemberChat($user1, $user2);
    }

    public function list(
        Model $actor,
        ChatTypeEnum $type,
        int $perPage = 15,
    ): LengthAwarePaginator {
        $handler = $this->registry->get($type);

        return $this->listAction->handle($actor, $handler, $perPage);
    }

    public function messages(
        Conversation $conversation,
        Model $actor,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->listMessagesAction->handle($conversation, $actor, $perPage);
    }

    public function send(
        Conversation $conversation,
        Model $actor,
        ChatMessageData $data,
        ChatTypeEnum $type,
    ): ConversationMessage {
        $handler = $this->registry->get($type);

        return $this->sendAction->handle($conversation, $actor, $data, $handler);
    }

    public function getHandler(ChatTypeEnum $type): ChatTypeHandlerInterface
    {
        return $this->registry->get($type);
    }
}
