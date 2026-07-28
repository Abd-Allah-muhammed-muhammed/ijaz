<?php

namespace Modules\Support\Services;

use App\Models\Admin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;
use Modules\Chat\Models\System;
use Modules\Chat\Services\ConversationService;
use Modules\Support\Infrastructure\Features\SupportChat;
use Modules\Support\Models\TicketSupport;

class TicketSupportChatService
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    public function ensureConversation(TicketSupport $ticket): Conversation
    {
        return $this->conversationService->ensureForOperation(
            $ticket,
            $this->systemParticipant(),
            $ticket->user,
        );
    }

    public function sendAsAdmin(
        TicketSupport $ticket,
        Admin $admin,
        ChatMessageData $data,
    ): ConversationMessage {
        $conversation = (new SupportChat($ticket))->replyAsAdmin(
            $admin,
            $data->content,
            $data->files ?? [],
        );

        return $conversation->lastMessage->loadMissing(['sender', 'attachments']);
    }

    public function sendAsUser(
        TicketSupport $ticket,
        ChatMessageData $data,
    ): ConversationMessage {
        $conversation = (new SupportChat($ticket))->replyAsSupportable(
            $data->content,
            $data->files ?? [],
        );

        return $conversation->lastMessage->loadMissing(['sender', 'attachments']);
    }

    public function listMessages(
        Conversation $conversation,
        Model $actor,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->conversationService->messages($conversation, $actor, $perPage);
    }

    private function systemParticipant(): System
    {
        return System::query()->firstOrCreate(
            ['id' => 1],
            ['name' => 'System', 'online' => false],
        );
    }
}
