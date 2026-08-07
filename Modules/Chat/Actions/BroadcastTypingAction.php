<?php

namespace Modules\Chat\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Modules\Chat\Contracts\HasConversation;
use Modules\Chat\Infrastructure\Events\UserTypingEvent;
use Modules\Chat\Models\Conversation;

class BroadcastTypingAction
{
    /**
     * @param  Model&HasConversation  $actor
     */
    public function handle(Conversation $conversation, Model $actor): void
    {
        // TEMP DEBUG
        Log::info('[TYPING DEBUG] Broadcasting typing event', [
            'conversation_id' => $conversation->id,
            'channel' => "chats.{$conversation->id}",
            'user_socket_id' => $actor->getAuthIdentifierForBroadcasting(),
        ]);

        broadcast(new UserTypingEvent($conversation, $actor))->toOthers();
    }
}
