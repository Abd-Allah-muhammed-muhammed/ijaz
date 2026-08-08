<?php

namespace Modules\Orders\Actions\Dashboard;

use App\Models\Admin;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Models\ConversationMessage;
use Modules\Chat\Support\AdminInterventionMessenger;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SendAdminOrderConversationMessageAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(Order $order, Admin $admin, ChatMessageData $data): ConversationMessage
    {
        $conversation = $this->orders->findConversation($order);

        if ($conversation === null) {
            throw new NotFoundHttpException('No conversation found for this order.');
        }

        return (new AdminInterventionMessenger($conversation))->sendAs(
            $admin,
            $data->content,
            $data->files ?? [],
        )->loadMissing(['sender', 'media']);
    }
}
