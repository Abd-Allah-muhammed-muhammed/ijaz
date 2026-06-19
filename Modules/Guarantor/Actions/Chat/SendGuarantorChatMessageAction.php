<?php

namespace Modules\Guarantor\Actions\Chat;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Actions\SendMessageAction;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Registry\ChatTypeRegistry;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Throwable;

class SendGuarantorChatMessageAction
{
    public function __construct(
        private readonly SendMessageAction $sendMessageAction,
        private readonly ChatTypeRegistry $chatTypeRegistry,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        Conversation $conversation,
        Model $sender,
        ChatMessageData $data,
    ): ConversationMessage {
        if ($conversation->operation_type !== GuarantorRequest::class) {
            throw new GuarantorException('guarantor.not_found', 404);
        }

        $message = $this->sendMessageAction->handle(
            $conversation,
            $sender,
            $data,
            $this->chatTypeRegistry->get(ChatTypeEnum::Guarantor),
        );

        $message->loadMissing(['sender', 'attachments']);

        return $message;
    }
}
