<?php

namespace App\Services\Chat\Jobs;

use App\Models\ConversationMessage;
use App\Services\Chat\Notifications\NewMessageSentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyChatMessageReceiver implements ShouldDispatchAfterCommit, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public ConversationMessage $message, public User $sender, public User $receiver, public $route = '/chatRoom') {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->receiver
            ->notify(new NewMessageSentNotification(
                $this->message->content,
                $this->sender,
                $this->message->attachments->isNotEmpty(),
                $this->message->conversation_id,
                $this->route,
            ));
    }
}
