<?php

namespace Modules\Orders\Actions;

use App\Contracts\Auth\AdminRepositoryInterface;
use Illuminate\Support\Facades\Notification;
use Modules\Orders\Notifications\StuckOrderSettlementsNotification;

class NotifyAdminsOfStuckOrderSettlementsAction
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
    ) {}

    public function handle(int $stuckCount): void
    {
        if ($stuckCount <= 0) {
            return;
        }

        $admins = $this->adminRepository->getWithPermission('show orders');

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new StuckOrderSettlementsNotification($stuckCount));
    }
}
