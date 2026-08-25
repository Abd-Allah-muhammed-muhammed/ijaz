<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Collection;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;

class ListProviderHomeRecommendedOrdersAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * @return Collection<int, Order>
     */
    public function handle(Provider $provider, int $limit = 10, ?array $categoryIds = null): Collection
    {
        return $this->orders->listRecommendedForProviderHome($provider, $limit, $categoryIds);
    }
}
