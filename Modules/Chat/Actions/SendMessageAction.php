<?php

namespace Modules\Chat\Actions;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Contracts\ChatTypeHandlerInterface;
use Modules\Chat\DTOs\ChatMessageData;
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
