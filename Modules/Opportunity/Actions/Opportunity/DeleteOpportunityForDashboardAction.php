<?php

namespace Modules\Opportunity\Actions\Opportunity;

use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\Models\Opportunity;

class DeleteOpportunityForDashboardAction
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $repository,
    ) {}

    /**
     * Admin dashboard soft-delete — no status restriction.
     * Distinct from DeleteOpportunityAction (API New-only delete).
     */
    public function handle(Opportunity $opportunity): void
    {
        $this->repository->delete($opportunity);
    }
}
