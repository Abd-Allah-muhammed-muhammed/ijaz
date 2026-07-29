<?php

namespace Modules\Guarantor\Actions\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Actions\OpenConversationAction;
use Modules\Chat\Enums\ChatTypeEnum;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Registry\ChatTypeRegistry;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Throwable;

class OpenGuarantorChatAction
{
    public function __construct(
        private readonly OpenConversationAction $openConversationAction,
        private readonly ChatTypeRegistry $chatTypeRegistry,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(GuarantorRequest $request, Model $actor): Conversation
    {
        return DB::transaction(function () use ($request, $actor) {
            $request->loadMissing(['requester', 'counterparty']);

            if (! in_array($request->status, [
                GuarantorStatusEnum::Accepted,
                GuarantorStatusEnum::InProgress,
                GuarantorStatusEnum::Overdue,
            ], true)) {
                throw new GuarantorException('guarantor.chat_not_allowed', 422);
            }

            return $this->openConversationAction->handle(
                $actor,
                $request,
                $this->chatTypeRegistry->get(ChatTypeEnum::Guarantor),
            );
        });
    }
}
