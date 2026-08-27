<?php

namespace Modules\Guarantor\Actions\Guarantor;

use App\Contracts\Auth\AdminRepositoryInterface;
use Illuminate\Support\Facades\Notification;
use Modules\Guarantor\Enums\GuarantorWithdrawnNotificationAudience;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorWithdrawnNotification;

/**
 * Fans out GuarantorWithdrawnNotification to Admins who can manage guarantors
 * (`manage guarantors`).
 */
class NotifyAdminsOfGuarantorWithdrawnAction
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
    ) {}

    public function handle(GuarantorRequest $guarantorRequest, ?string $reason): void
    {
        $admins = $this->adminRepository->getWithPermission('manage guarantors');

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send(
            $admins,
            new GuarantorWithdrawnNotification(
                $guarantorRequest,
                GuarantorWithdrawnNotificationAudience::Admin,
                $reason,
            ),
        );
    }
}
