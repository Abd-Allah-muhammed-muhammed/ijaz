<?php

namespace Modules\Orders\Actions\Dashboard;

use App\Models\Admin;
use Modules\Chat\Services\ConversationService;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BroadcastAdminOrderConversationTypingAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ConversationService $conversations,
    ) {}

    public function handle(Order $order, Admin $admin): void
    {
        $conversation = $this->orders->findConversation($order);

        if ($conversation === null) {
            throw new NotFoundHttpException('No conversation found for this order.');
        }

        $this->conversations->typing($conversation, $admin);
    }
}
