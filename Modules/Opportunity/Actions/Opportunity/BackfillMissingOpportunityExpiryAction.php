<?php

namespace Modules\Opportunity\Actions\Opportunity;

use Illuminate\Support\Carbon;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\Models\Opportunity;

class BackfillMissingOpportunityExpiryAction
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $opportunities,
    ) {}

    /**
     * Fill null expires_at values only. Never overwrites an existing expiry.
     *
     * @return int Number of opportunities matched (and updated when not dry-run)
     */
    public function handle(bool $dryRun = false): int
    {
        $count = 0;

        foreach ($this->opportunities->getMissingExpiry() as $opportunity) {
            $count++;

            if ($dryRun) {
                continue;
            }

            $this->opportunities->update($opportunity, [
                'expires_at' => $this->resolveExpiresAt($opportunity),
            ]);
        }

        return $count;
    }

    private function resolveExpiresAt(Opportunity $opportunity): Carbon
    {
        $fromCreatedAt = $opportunity->created_at
            ->copy()
            ->addDays(Opportunity::DEFAULT_DURATION_DAYS);

        if ($fromCreatedAt->isPast()) {
            return now()->addDays(Opportunity::DEFAULT_DURATION_DAYS);
        }

        return $fromCreatedAt;
    }
}
