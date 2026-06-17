<?php

namespace Modules\Guarantor\Actions\Chat;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Http\Requests\SendMessageRequest;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Support\GuarantorConversationMessenger;
use Throwable;

class SendGuarantorChatMessageAction
{
    /**
     * @throws Throwable
     */
    public function handle(
        Conversation $conversation,
        Model $sender,
        SendMessageRequest $request,
    ): ConversationMessage {
        if ($conversation->operation_type !== GuarantorRequest::class) {
            throw new GuarantorException('guarantor.not_found', 404);
        }

        $messenger = new GuarantorConversationMessenger($conversation);
        $updatedConversation = $messenger->sendAs(
            $sender,
            $request->input('content'),
            $request->file('files', []),
        );
        $updatedConversation->loadMissing(['lastMassage.sender', 'lastMassage.attachments']);

        return $updatedConversation->lastMassage;
    }
}
