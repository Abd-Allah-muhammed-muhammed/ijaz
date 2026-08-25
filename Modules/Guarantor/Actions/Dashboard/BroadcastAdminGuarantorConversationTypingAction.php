<?php

namespace Modules\Guarantor\Actions\Dashboard;

use App\Models\Admin;
use Modules\Chat\Services\ConversationService;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Models\GuarantorRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BroadcastAdminGuarantorConversationTypingAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantors,
        private readonly ConversationService $conversations,
    ) {}

    /**
     * Admin HTTP routes bind the GuarantorRequest (`/guarantor/{id}/conversation-typing`).
     * Presence listeners join `chats.{conversation_id}` — never `chats.{guarantor_id}`.
     */
    public function handle(GuarantorRequest $guarantorRequest, Admin $admin): void
    {
        $conversation = $this->guarantors->findConversation($guarantorRequest);

        if ($conversation === null) {
            throw new NotFoundHttpException('No conversation found for this guarantor request.');
        }

        $this->conversations->typing($conversation, $admin);
    }
}
