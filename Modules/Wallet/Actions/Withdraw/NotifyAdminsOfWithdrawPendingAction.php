<?php

namespace Modules\Wallet\Actions\Withdraw;

use App\Contracts\Auth\AdminRepositoryInterface;
use Illuminate\Support\Facades\Notification;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Notifications\WithdrawPendingReviewNotification;

/**
 * Fans out WithdrawPendingReviewNotification to Admins who can view withdraws
 * (`show withdrawRequests`), matching dashboard WithdrawRequestController authorization.
 */
class NotifyAdminsOfWithdrawPendingAction
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
    ) {}

    public function handle(WithdrawRequest $withdrawRequest): void
    {
        $admins = $this->adminRepository->getWithPermission('show withdrawRequests');

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new WithdrawPendingReviewNotification($withdrawRequest));
    }
}
