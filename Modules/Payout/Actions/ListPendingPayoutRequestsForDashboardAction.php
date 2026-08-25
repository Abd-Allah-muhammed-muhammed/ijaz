<?php

namespace Modules\Payout\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Payout\Contracts\Repositories\PayoutRequestRepositoryInterface;

class ListPendingPayoutRequestsForDashboardAction
{
    public function __construct(
        private readonly PayoutRequestRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateActionableForDashboard($request);
    }
}
