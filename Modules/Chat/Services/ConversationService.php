<?php

namespace Modules\Chat\Services;

use App\Models\Admin;
use App\Models\TicketSupport;
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
use Modules\Chat\Models\System;
use Modules\Chat\Registry\ChatTypeRegistry;

class ConversationService
{
    public function __construct(
        private readonly ChatTypeRegistry $registry,
        private readonly OpenConversationAction $openAction,
        private readonly ListConversationsAction $listAction,
        private readonly ListMessagesAction $listMessagesAction,
        private readonly SendMessageAction $sendAction,
        private readonly ChatService $chatService,
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

    public function ensureTicketSupportConversation(TicketSupport $ticket): Conversation
    {
        return Conversation::query()->firstOrCreate([
            'operation_type' => TicketSupport::class,
            'operation_id' => $ticket->getKey(),
        ], [
            'user1_type' => System::class,
            'user1_id' => 1,
            'user2_type' => $ticket->user_type,
            'user2_id' => $ticket->user_id,
        ]);
    }

    public function sendTicketSupportAsAdmin(
        TicketSupport $ticket,
        Admin $admin,
        ChatMessageData $data,
    ): ConversationMessage {
        $conversation = $this->chatService->support($ticket)->replyAsAdmin(
            $admin,
            $data->content,
            $data->files ?? [],
        );

        return $conversation->lastMessage->loadMissing(['sender', 'attachments']);
    }

    public function sendTicketSupportAsUser(
        TicketSupport $ticket,
        ChatMessageData $data,
    ): ConversationMessage {
        $conversation = $this->chatService->support($ticket)->replyAsSupportable(
            $data->content,
            $data->files ?? [],
        );

        return $conversation->lastMessage->loadMissing(['sender', 'attachments']);
    }
}
