<?php

namespace Modules\Opportunity\Support;

use App\Models\Conversation;
use App\Services\Chat\Contracts\HasConversation;
use App\Services\Chat\Features\BaseChatService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use RuntimeException;

class OpportunityConversationMessenger extends BaseChatService
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
        $this->chat->loadMissing(['user1', 'user2']);

        $receiver = $this->chat->user1()->is($sender)
            ? $this->chat->user2
            : $this->chat->user1;

        if (! $receiver instanceof HasConversation) {
            throw new RuntimeException('Receiver must implement HasConversation.');
        }

        return $receiver;
    }
}
