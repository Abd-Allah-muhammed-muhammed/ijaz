<?php

namespace Modules\Orders\Actions\Dashboard;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;

class ListDashboardOrdersAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * @param  array{status?: mixed, date_from?: mixed, date_to?: mixed}  $filters
     */
    public function handle(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->orders->paginateForDashboard($filters, $perPage);
    }

    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        return $this->orders->dashboardStats();
    }
}
