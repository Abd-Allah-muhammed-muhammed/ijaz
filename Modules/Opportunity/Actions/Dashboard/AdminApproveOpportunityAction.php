<?php

namespace Modules\Opportunity\Actions\Dashboard;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Notifications\OpportunityAdminApprovedNotification;
use Throwable;

class AdminApproveOpportunityAction
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $opportunities,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Opportunity $opportunity, ?string $notes, Admin $admin): Opportunity
    {
        return DB::transaction(function () use ($opportunity) {
            $opportunity = $this->opportunities->findForUpdate($opportunity);

            if ($opportunity->status->isNot(OpportunityStatusEnum::PendingAdmin)) {
                throw new OpportunityException('opportunity.status_transition_not_allowed', 422);
            }

            $opportunity = $this->opportunities->update($opportunity, [
                'status' => OpportunityStatusEnum::New,
            ]);

            $opportunity->loadMissing('author');
            $opportunity->author->notify(new OpportunityAdminApprovedNotification($opportunity));

            return $opportunity;
        });
    }
}
