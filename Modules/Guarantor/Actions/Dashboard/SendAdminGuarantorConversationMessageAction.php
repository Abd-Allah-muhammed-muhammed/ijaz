<?php

namespace Modules\Guarantor\Actions\Dashboard;

use App\Models\Admin;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Models\ConversationMessage;
use Modules\Chat\Support\AdminInterventionMessenger;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Models\GuarantorRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SendAdminGuarantorConversationMessageAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantors,
    ) {}

    public function handle(GuarantorRequest $guarantorRequest, Admin $admin, ChatMessageData $data): ConversationMessage
    {
        $conversation = $this->guarantors->findConversation($guarantorRequest);

        if ($conversation === null) {
            throw new NotFoundHttpException('No conversation found for this guarantor request.');
        }

        return (new AdminInterventionMessenger($conversation))->sendAs(
            $admin,
            $data->content,
            $data->files ?? [],
        )->loadMissing(['sender', 'media']);
    }
}
