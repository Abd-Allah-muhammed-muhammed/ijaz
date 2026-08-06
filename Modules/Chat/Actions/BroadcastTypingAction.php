<?php

namespace Modules\Chat\Actions;

use Illuminate\Database\Eloquent\Model;
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
        broadcast(new UserTypingEvent($conversation, $actor))->toOthers();
    }
}
