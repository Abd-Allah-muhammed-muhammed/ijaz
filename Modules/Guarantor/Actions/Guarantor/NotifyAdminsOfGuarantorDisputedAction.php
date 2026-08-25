<?php

namespace Modules\Guarantor\Actions\Guarantor;

use App\Contracts\Auth\AdminRepositoryInterface;
use Illuminate\Support\Facades\Notification;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorDisputedNotification;

/**
 * Fans out GuarantorDisputedNotification to Admins who can manage guarantors
 * (`manage guarantors`).
 */
class NotifyAdminsOfGuarantorDisputedAction
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
    ) {}

    public function handle(GuarantorRequest $guarantorRequest, string $reason): void
    {
        $admins = $this->adminRepository->getWithPermission('manage guarantors');

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new GuarantorDisputedNotification($guarantorRequest, $reason));
    }
}
