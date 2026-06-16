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
    public function handle(Opportunity $opportunity, ?Carbon $expiresAt = null): Opportunity
    {
        return DB::transaction(function () use ($opportunity, $expiresAt) {
            if ($opportunity->status->isNotIn([
                OpportunityStatusEnum::New,
                OpportunityStatusEnum::OfferAccepted,
            ])) {
                throw new OpportunityException('opportunity.cannot_renew', 422);
            }

            $newExpiresAt = $expiresAt ?? now()->max($opportunity->expires_at)->addDays(7);

            $opportunity->update(['expires_at' => $newExpiresAt]);

            return $opportunity->fresh();
        });
    }
}
