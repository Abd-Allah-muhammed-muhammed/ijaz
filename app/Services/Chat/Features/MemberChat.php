<?php

namespace App\Services\Chat\Features;

use App\Models\Conversation;
use App\Services\Chat\Contracts\HasConversation;
use App\Services\Chat\Exceptions\ChatException;
use App\Services\Chat\Exceptions\ChatMessageException;
use App\Services\Chat\Exceptions\ChatUerException;
use Illuminate\Http\UploadedFile;
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
    public function replay(?string $message = null, array $attachments = []): Conversation
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
     * @throws ChatUerException
     */
    protected function getReceiver(HasConversation $sender): HasConversation
    {
        [$user_type, $user_id] = [get_class($sender), $sender->getKey()];
        if ($this->chat->user1_type == $user_type && $this->chat->user1_id == $user_id) {
            $receiver = $this->chat->user2;
        } elseif ($this->chat->user2_type == $user_type && $this->chat->user2_id == $user_id) {
            $receiver = $this->chat->user1;
        } else {
            throw ChatUerException::senderDoesNotBelongToChat();
        }

        return $receiver;
    }
}
