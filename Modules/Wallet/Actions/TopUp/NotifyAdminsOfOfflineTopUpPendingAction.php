<?php

namespace Modules\Wallet\Actions\TopUp;

use App\Contracts\Auth\AdminRepositoryInterface;
use Illuminate\Support\Facades\Notification;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Notifications\OfflineTopUpPendingReviewNotification;

/**
 * Fans out OfflineTopUpPendingReviewNotification to Admins who can view top-ups
 * (`show topUpRequests`), matching dashboard TopUpRequestController authorization.
 */
class NotifyAdminsOfOfflineTopUpPendingAction
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
    ) {}

    public function handle(TopUpRequest $topUpRequest): void
    {
        $admins = $this->adminRepository->getWithPermission('show topUpRequests');

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new OfflineTopUpPendingReviewNotification($topUpRequest));
    }
}
