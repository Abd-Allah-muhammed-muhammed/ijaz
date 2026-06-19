<?php

namespace Modules\Chat\Support;

use App\Models\Conversation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Modules\Chat\Contracts\HasConversation;
use Modules\Chat\Infrastructure\BaseChatService;
use RuntimeException;

class ParticipantConversationMessenger extends BaseChatService
{
    public function __construct(Conversation $conversation)
    {
        $this->chat = $conversation;
    }

    /**
     * @param  array<int, UploadedFile>  $attachments
     */
    public function sendAs(Model $sender, ?string $message = null, array $attachments = []): Conversation
    {
        if (! $sender instanceof HasConversation) {
            throw new RuntimeException('Sender must implement HasConversation.');
        }

        $this->chat->loadMissing(['user1', 'user2']);

        return $this->send($sender, $message, $attachments);
    }

    public function getOnlineUsers(): Collection
    {
        if (app()->runningUnitTests()) {
            return collect();
        }

        try {
            return parent::getOnlineUsers();
        } catch (\Throwable) {
            return collect();
        }
    }

    protected function getReceiver(HasConversation $sender): HasConversation
    {
        $conversation = $this->chat;

        if (
            $conversation->user1_type === $sender::class
            && (string) $conversation->user1_id === (string) $sender->getKey()
        ) {
            $receiver = $conversation->user2;
        } else {
            $receiver = $conversation->user1;
        }

        if (! $receiver instanceof HasConversation) {
            throw new RuntimeException('Receiver must implement HasConversation.');
        }

        return $receiver;
    }
}
