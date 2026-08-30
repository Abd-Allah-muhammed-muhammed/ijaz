<?php

namespace Modules\Opportunity\Actions\Opportunity;

use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Actions\Opportunity\NotifyAdminsOfOpportunityPendingAction as NotifyAdminsOfOpportunityPending;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Models\Opportunity;
use Throwable;

class ResubmitOpportunityAction
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $opportunities,
        private readonly NotifyAdminsOfOpportunityPending $notifyAdminsOfOpportunityPendingAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Opportunity $opportunity): Opportunity
    {
        return DB::transaction(function () use ($opportunity) {
            $opportunity = $this->opportunities->findForUpdate($opportunity);

            if ($opportunity->status->isNot(OpportunityStatusEnum::RejectedByAdmin)) {
                throw new OpportunityException('opportunity.status_transition_not_allowed', 422);
            }

            $opportunity = $this->opportunities->update($opportunity, [
                'status' => OpportunityStatusEnum::PendingAdmin,
                'rejection_reason' => null,
            ]);

            $this->notifyAdminsOfOpportunityPendingAction->handle($opportunity);

            return $opportunity->fresh(['author', 'region.translation', 'city.translation', 'media']) ?? $opportunity;
        });
    }
}
