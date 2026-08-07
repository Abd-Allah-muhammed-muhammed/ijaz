<?php

namespace Modules\Chat\Support;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Modules\Chat\Contracts\HasConversation;
use Modules\Chat\Infrastructure\BaseChatService;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;
use RuntimeException;

/**
 * Sends as an Admin who is not a conversation participant (support intervention).
 * Message row receiver is user1; both participants are notified of the update.
 */
class AdminInterventionMessenger extends BaseChatService
{
    public function __construct(Conversation $conversation)
    {
        $this->chat = $conversation;
    }

    /**
     * @param  array<int, UploadedFile>  $attachments
     */
    public function sendAs(Admin $admin, ?string $message = null, array $attachments = []): Conversation
    {
        $this->chat->loadMissing(['user1', 'user2']);

        return $this->send($admin, $message, $attachments);
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
        $receiver = $this->chat->user1;

        if (! $receiver instanceof HasConversation) {
            throw new RuntimeException('Receiver must implement HasConversation.');
        }

        return $receiver;
    }

    protected function notifyReceiver(
        ConversationMessage $message,
        HasConversation $sender,
        HasConversation $receiver,
    ): void {
        $this->chat->loadMissing(['user1', 'user2']);

        foreach ([$this->chat->user1, $this->chat->user2] as $participant) {
            if ($participant instanceof HasConversation && $participant instanceof Model) {
                parent::notifyReceiver($message, $sender, $participant);
            }
        }
    }
}
