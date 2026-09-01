<?php

namespace Modules\Orders\Actions;

use App\Contracts\Auth\AdminRepositoryInterface;
use Illuminate\Support\Facades\Notification;
use Modules\Orders\Models\Order;
use Modules\Orders\Notifications\OrderDisputedNotification;

/**
 * Fans out OrderDisputedNotification to Admins who can manage orders.
 */
class NotifyAdminsOfOrderDisputedAction
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
    ) {}

    public function handle(Order $order, string $reason): void
    {
        $admins = $this->adminRepository->getWithPermission('manage orders');

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new OrderDisputedNotification($order, $reason));
    }
}
