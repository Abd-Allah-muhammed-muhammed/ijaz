<?php

namespace Modules\Support\Services;

use App\Models\Admin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Chat\Contracts\Repositories\ConversationMessageRepositoryInterface;
use Modules\Chat\Contracts\Repositories\SystemRepositoryInterface;
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
        private readonly SystemRepositoryInterface $systemRepository,
        private readonly ConversationMessageRepositoryInterface $messageRepository,
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

        return $conversation->lastMessage->loadMissing(['sender', 'media', 'attachments']);
    }

    public function sendAsUser(
        TicketSupport $ticket,
        ChatMessageData $data,
    ): ConversationMessage {
        $conversation = (new SupportChat($ticket))->replyAsSupportable(
            $data->content,
            $data->files ?? [],
        );

        return $conversation->lastMessage->loadMissing(['sender', 'media', 'attachments']);
    }

    public function listMessages(
        Conversation $conversation,
        Model $actor,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->conversationService->messages($conversation, $actor, $perPage);
    }

    /**
     * Dashboard ticket show: last N messages in chronological order.
     *
     * @return Collection<int, ConversationMessage>
     */
    public function listRecentMessages(TicketSupport $ticket, int $limit = 20): Collection
    {
        if (! $ticket->chat) {
            return collect();
        }

        return $this->messageRepository->listRecentForConversation($ticket->chat, $limit);
    }

    private function systemParticipant(): System
    {
        return $this->systemRepository->findOrCreateDefault();
    }
}
