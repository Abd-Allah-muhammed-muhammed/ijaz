<?php

namespace Modules\Opportunity\Actions\Opportunity;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Models\Opportunity;
use Throwable;

class RenewOpportunityAction
{
    /**
     * @throws Throwable
     */
    public function handle(Opportunity $opportunity): Opportunity
    {
        return DB::transaction(function () use ($opportunity) {
            if (! $opportunity->status->isIn([
                OpportunityStatusEnum::New,
                OpportunityStatusEnum::OfferAccepted,
            ])) {
                throw new OpportunityException('opportunity.cannot_renew', 422);
            }

            $baseDate = max(
                now(),
                $opportunity->expires_at ?? now(),
            );

            $opportunity->update([
                'expires_at' => Carbon::parse($baseDate)->addDays(7),
            ]);

            return $opportunity->fresh();
        });
    }
}
