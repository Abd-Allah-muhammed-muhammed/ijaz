<?php

namespace Modules\Guarantor\Services;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Guarantor\Actions\Chat\ListGuarantorChatMessagesAction;
use Modules\Guarantor\Actions\Chat\ListGuarantorChatsAction;
use Modules\Guarantor\Actions\Chat\OpenGuarantorChatAction;
use Modules\Guarantor\Actions\Chat\SendGuarantorChatMessageAction;
use Modules\Guarantor\Models\GuarantorRequest;
use Throwable;

class GuarantorChatService
{
    public function __construct(
        private readonly OpenGuarantorChatAction $openChatAction,
        private readonly ListGuarantorChatsAction $listChatsAction,
        private readonly ListGuarantorChatMessagesAction $listMessagesAction,
        private readonly SendGuarantorChatMessageAction $sendMessageAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function open(GuarantorRequest $request, Model $actor): Conversation
    {
        return $this->openChatAction->handle($request, $actor);
    }

    public function listForActor(Model $actor, int $perPage = 15): LengthAwarePaginator
    {
        return $this->listChatsAction->handle($actor, $perPage);
    }

    public function listMessages(
        Conversation $conversation,
        Model $actor,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return $this->listMessagesAction->handle($conversation, $actor, $perPage);
    }

    /**
     * @throws Throwable
     */
    public function send(
        Conversation $conversation,
        Model $sender,
        ChatMessageData $data,
    ): ConversationMessage {
        return $this->sendMessageAction->handle(
            $conversation,
            $sender,
            $data,
        );
    }
}
