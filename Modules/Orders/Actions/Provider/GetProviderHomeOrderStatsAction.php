<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;

class GetProviderHomeOrderStatsAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * @return array{totalOrders: int, totalFinishedOrders: int}
     */
    public function handle(Provider $provider): array
    {
        return $this->orders->providerHomeStats($provider);
    }
}
