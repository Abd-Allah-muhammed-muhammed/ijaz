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
            if ($opportunity->status->isNotIn([
                OpportunityStatusEnum::New,
                OpportunityStatusEnum::OfferAccepted,
            ])) {
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
