<?php

namespace Modules\Support\Infrastructure\Features;

use App\Models\Admin;
use http\Exception\RuntimeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Modules\Chat\Contracts\HasConversation;
use Modules\Chat\Exceptions\ChatException;
use Modules\Chat\Exceptions\ChatMessageException;
use Modules\Chat\Infrastructure\BaseChatService;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\System;
use Modules\Support\Models\TicketSupport;
use Pusher\ApiErrorException;

class SupportChat extends BaseChatService
{
    protected Model $supportable;

    public function __construct(protected TicketSupport $ticket)
    {
        $this->generateChat();
    }

    /**
     * creating support chat for user
     */
    protected function generateChat(): void
    {
        $this->chat = $this->ticket->chat()->firstOrCreate([
            'operation_id' => $this->ticket->getKey(),
            'operation_type' => get_class($this->ticket),
        ], [
            'user1_type' => System::class,
            'user1_id' => 1,
            'user2_type' => get_class($this->ticket->user),
            'user2_id' => $this->ticket->user->getKey(),
        ]);
        $this->supportable = $this->ticket->user;

    }

    public function getChat(): ?Conversation
    {
        return $this->chat;
    }

    /**
     * send message to user as provided admin
     *
     * @param  UploadedFile[]  $attachments
     *
     * @throws ChatException
     * @throws ChatMessageException|ApiErrorException
     */
    public function replyAsAdmin(Admin $admin, ?string $message = null, array $attachments = []): Conversation
    {
        $this->validate($message, $attachments, ['admin']);

        return $this->send($admin, $message, $attachments);
    }

    /**
     * replay as support chat user
     *
     * @param  UploadedFile[]  $attachments
     *
     * @throws ApiErrorException /
     * @throws ChatException /
     * @throws ChatMessageException /
     */
    public function replyAsSupportable(?string $message = null, array $attachments = []): Conversation
    {
        $this->validate($message, $attachments, ['supportable']);

        return $this->send($this->supportable, $message, $attachments);
    }

    /**
     * setting chat object to send messages
     *
     * @return $this
     *
     * @throws ChatException
     */
    public function setChat(?Conversation $chat): static
    {
        if ($chat === null) {
            throw ChatException::nullable();
        }
        if ($chat->operation()->isNot($this->ticket)) {
            throw new RuntimeException("The provided chat does not belong to the ticket support #{$this->ticket->getKey()}");
        }
        $this->chat = $chat;
        $this->supportable = $this->ticket->user;

        return $this;
    }

    /**
     * validate chat
     *
     * @throws ChatException
     */
    protected function validateChat(?string $message = null, ?Collection $attachments = null, array $extra = []): void
    {
        parent::validateChat($message, $attachments, []);
        [$as] = $extra;
        if ($as === 'admin' && $this->chat->user1_type != System::class) {
            throw ChatException::notSupportChat();
        }
        if ($as === 'supportable' && $this->chat->user2->isNot($this->supportable)) {
            throw ChatException::chatDoesnotBelongToUser("({$this->supportable->getType()}-{$this->supportable->getKey()})");
        }
    }

    /**
     * resolve receiver object from chat
     */
    protected function getReceiver(HasConversation $sender): HasConversation
    {
        if ($sender instanceof Admin) {
            return $this->chat->user2;
        }

        return $this->chat->user1;
    }
}
