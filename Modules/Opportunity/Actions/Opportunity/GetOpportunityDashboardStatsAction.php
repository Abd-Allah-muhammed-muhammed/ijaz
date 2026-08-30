<?php

namespace Modules\Opportunity\Actions\Opportunity;

use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;

class GetOpportunityDashboardStatsAction
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $repository,
    ) {}

    /**
     * @return array{total: int, pending_admin: int}
     */
    public function handle(): array
    {
        return $this->repository->getDashboardStats();
    }
}
