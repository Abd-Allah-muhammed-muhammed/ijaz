<?php

namespace Modules\Guarantor\Actions\Chat;

use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Throwable;

class OpenGuarantorChatAction
{
    /**
     * @throws Throwable
     */
    public function handle(GuarantorRequest $request): Conversation
    {
        return DB::transaction(function () use ($request) {
            $request->loadMissing(['requester', 'counterparty']);

            if (! in_array($request->status, [
                GuarantorStatusEnum::InProgress,
                GuarantorStatusEnum::Overdue,
            ], true)) {
                throw new GuarantorException('guarantor.chat_not_allowed', 422);
            }

            return Conversation::query()->firstOrCreate(
                [
                    'operation_type' => GuarantorRequest::class,
                    'operation_id' => $request->id,
                ],
                [
                    'user1_id' => $request->requester->getKey(),
                    'user1_type' => $request->requester::class,
                    'user2_id' => $request->counterparty->getKey(),
                    'user2_type' => $request->counterparty::class,
                ],
            );
        });
    }
}
