<?php

namespace Modules\Opportunity\Actions\Opportunity;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;

class ListOpportunitiesForDashboardAction
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateForDashboard($request);
    }
}
