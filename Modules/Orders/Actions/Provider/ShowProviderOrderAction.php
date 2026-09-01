<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use Illuminate\Support\Facades\Gate;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;

class ShowProviderOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(Order $order, Provider $provider): Order
    {
        if (! Gate::forUser($provider)->allows('viewAsProvider', $order)) {
            abort(404);
        }

        return $this->orders->loadForProviderShow($order, $provider);
    }
}
