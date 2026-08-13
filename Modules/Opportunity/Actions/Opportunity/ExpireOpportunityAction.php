<?php

namespace Modules\Opportunity\Actions\Opportunity;

use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Notifications\OpportunityExpiredNotification;
use Throwable;

class ExpireOpportunityAction
{
    /**
     * @throws Throwable
     */
    public function handle(Opportunity $opportunity): void
    {
        DB::transaction(function () use ($opportunity) {
            $opportunity = Opportunity::query()
                ->lockForUpdate()
                ->find($opportunity->getKey());

            if ($opportunity === null) {
                return;
            }

            if ($opportunity->status->isNotIn([
                OpportunityStatusEnum::New,
                OpportunityStatusEnum::OfferAccepted,
            ])) {
                return;
            }

            // Re-check at execution time: a renew between dispatch and run
            // pushes expires_at into the future while leaving status New.
            if ($opportunity->expires_at === null || $opportunity->expires_at->isAfter(now())) {
                return;
            }

            $opportunity->update(['status' => OpportunityStatusEnum::Expired]);

            $opportunity->loadMissing('author');

            $opportunity->author->notify(
                new OpportunityExpiredNotification($opportunity)
            );
        });
    }
}
