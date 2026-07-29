<?php

namespace Modules\Orders\Actions\Dashboard;

use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;

class GetOrderStatusDistributionAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * @return array<string, int>
     */
    public function handle(): array
    {
        return $this->orders->statusDistribution();
    }
}
