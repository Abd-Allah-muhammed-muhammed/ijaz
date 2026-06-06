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

        return $this->send($sender, $message, $attachments);
    }

    public function getOnlineUsers(): Collection
    {
        if (app()->runningUnitTests()) {
            return collect();
        }

        return parent::getOnlineUsers();
    }

    protected function getReviver(HasConversation $sender): HasConversation
    {
        if ($this->chat->user1()->is($sender)) {
            return $this->chat->user2;
        }

        return $this->chat->user1;
    }
}
