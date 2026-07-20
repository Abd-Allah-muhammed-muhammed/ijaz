<?php

namespace Modules\Chat\Infrastructure\Features;

use Illuminate\Http\UploadedFile;
use Modules\Chat\Contracts\HasConversation;
use Modules\Chat\Exceptions\ChatException;
use Modules\Chat\Exceptions\ChatMessageException;
use Modules\Chat\Exceptions\ChatUserException;
use Modules\Chat\Infrastructure\BaseChatService;
use Modules\Chat\Models\Conversation;
use Pusher\ApiErrorException;

class MemberChat extends BaseChatService
{
    protected HasConversation $sender;

    public function __construct(?Conversation $chat = null)
    {
        $this->chat = $chat;
    }

    /**
     * @param  UploadedFile[]  $attachments
     *
     * @throws ApiErrorException|ChatMessageException|ChatMessageException|ChatException
     */
    public function reply(?string $message = null, array $attachments = []): Conversation
    {
        $this->validate($message, $attachments);

        return $this->send($this->sender, $message, $attachments);
    }

    /**
     * @return $this
     */
    public function setSender(HasConversation $sender): MemberChat
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * @throws ChatUserException
     */
    protected function getReceiver(HasConversation $sender): HasConversation
    {
        [$user_type, $user_id] = [get_class($sender), $sender->getKey()];
        if ($this->chat->user1_type == $user_type && $this->chat->user1_id == $user_id) {
            $receiver = $this->chat->user2;
        } elseif ($this->chat->user2_type == $user_type && $this->chat->user2_id == $user_id) {
            $receiver = $this->chat->user1;
        } else {
            throw ChatUserException::senderDoesNotBelongToChat();
        }

        return $receiver;
    }
}
