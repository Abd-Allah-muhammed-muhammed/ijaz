<?php

namespace Modules\Opportunity\Actions\Chat;

use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\ConversationMessage;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Actions\SendMessageAction;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Registry\ChatTypeRegistry;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Models\Opportunity;
use Throwable;

class SendOpportunityChatMessageAction
{
    public function __construct(
        private readonly SendMessageAction $sendMessageAction,
        private readonly ChatTypeRegistry $chatTypeRegistry,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Conversation $conversation, Model $sender, ChatMessageData $data): ConversationMessage
    {
        if ($conversation->operation_type !== Opportunity::class) {
            throw new OpportunityException('opportunity.not_found', 404);
        }

        $message = $this->sendMessageAction->handle(
            $conversation,
            $sender,
            $data,
            $this->chatTypeRegistry->get(ChatTypeEnum::Opportunity),
        );

        $message->loadMissing(['sender', 'attachments']);

        return $message;
    }
}
