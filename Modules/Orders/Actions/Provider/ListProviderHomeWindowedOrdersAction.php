<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use Illuminate\Support\Collection;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;

class ListProviderHomeWindowedOrdersAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * @return Collection<string, Collection<int, Order>>
     */
    public function handle(Provider $provider): Collection
    {
        return $this->orders->listWindowedForProviderHome($provider);
    }
}
