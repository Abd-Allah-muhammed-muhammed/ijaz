<?php

namespace Modules\Guarantor\Actions\Guarantor;

use App\Contracts\Auth\AdminRepositoryInterface;
use Illuminate\Support\Facades\Notification;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorPendingReviewNotification;

/**
 * Fans out GuarantorPendingReviewNotification to Admins who can manage guarantors
 * (`manage guarantors`), matching dashboard GuarantorController approve/reject/cancel authorization.
 */
class NotifyAdminsOfGuarantorPendingAction
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
    ) {}

    public function handle(GuarantorRequest $guarantorRequest): void
    {
        $admins = $this->adminRepository->getWithPermission('manage guarantors');

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new GuarantorPendingReviewNotification($guarantorRequest));
    }
}
