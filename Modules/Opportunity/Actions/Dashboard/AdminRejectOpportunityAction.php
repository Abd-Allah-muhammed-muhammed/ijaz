<?php

namespace Modules\Opportunity\Actions\Dashboard;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Notifications\OpportunityAdminRejectedNotification;
use Throwable;

class AdminRejectOpportunityAction
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $opportunities,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        Opportunity $opportunity,
        string $reason,
        ?string $notes,
        Admin $admin,
    ): void {
        DB::transaction(function () use ($opportunity, $reason) {
            $opportunity = $this->opportunities->findForUpdate($opportunity);

            if ($opportunity->status->isNot(OpportunityStatusEnum::PendingAdmin)) {
                throw new OpportunityException('opportunity.status_transition_not_allowed', 422);
            }

            $opportunity = $this->opportunities->update($opportunity, [
                'status' => OpportunityStatusEnum::RejectedByAdmin,
            ]);

            $opportunity->loadMissing('author');
            $opportunity->author->notify(new OpportunityAdminRejectedNotification($opportunity, $reason));
        });
    }
}
