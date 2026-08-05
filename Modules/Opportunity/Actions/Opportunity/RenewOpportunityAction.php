<?php

namespace Modules\Opportunity\Actions\Opportunity;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Models\Opportunity;
use Throwable;

class RenewOpportunityAction
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $opportunities,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Opportunity $opportunity, ?Carbon $expiresAt = null): Opportunity
    {
        return DB::transaction(function () use ($opportunity, $expiresAt) {
            if ($opportunity->status->isNotIn([
                OpportunityStatusEnum::New,
                OpportunityStatusEnum::OfferAccepted,
                OpportunityStatusEnum::Expired,
            ])) {
                throw new OpportunityException('opportunity.cannot_renew', 422);
            }

            $opportunity = $this->opportunities->update($opportunity, [
                'expires_at' => $this->resolveNewExpiresAt($opportunity, $expiresAt),
                'status' => OpportunityStatusEnum::New,
            ]);

            return $opportunity->fresh();
        });
    }

    /**
     * Custom expires_at wins; otherwise extend from a future expiry, or from now when expired/null.
     */
    private function resolveNewExpiresAt(Opportunity $opportunity, ?Carbon $expiresAt): Carbon
    {
        if ($expiresAt !== null) {
            return $expiresAt->copy();
        }

        $current = $opportunity->expires_at;
        $base = ($current !== null && $current->isFuture())
            ? $current->copy()
            : now();

        return $base->addDays(Opportunity::DEFAULT_DURATION_DAYS);
    }
}
