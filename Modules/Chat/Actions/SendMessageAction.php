<?php

namespace Modules\Chat\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Contracts\ChatTypeHandlerInterface;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;
use Modules\Chat\Support\ParticipantConversationMessenger;

class SendMessageAction
{
    public function handle(
        Conversation $conversation,
        Model $sender,
        ChatMessageData $data,
        ChatTypeHandlerInterface $handler,
    ): ConversationMessage {
        $messenger = $handler->messenger($conversation);

        /** @var ParticipantConversationMessenger $messenger */
        $messenger->sendAs($sender, $data->content, $data->files ?? []);

        return $conversation->messages()->latest()->first();
    }
}
