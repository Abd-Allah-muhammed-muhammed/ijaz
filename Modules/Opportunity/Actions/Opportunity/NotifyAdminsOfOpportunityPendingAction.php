<?php

namespace Modules\Opportunity\Actions\Opportunity;

use App\Contracts\Auth\AdminRepositoryInterface;
use Illuminate\Support\Facades\Notification;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Notifications\OpportunityPendingReviewNotification;

/**
 * Fans out OpportunityPendingReviewNotification to Admins who can manage opportunities
 * (`manage opportunities`), matching dashboard OpportunityController approve/reject authorization.
 */
class NotifyAdminsOfOpportunityPendingAction
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
    ) {}

    public function handle(Opportunity $opportunity): void
    {
        $admins = $this->adminRepository->getWithPermission('manage opportunities');

        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new OpportunityPendingReviewNotification($opportunity));
    }
}
