<?php

namespace Modules\Orders\Policies;

use App\Models\Provider;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;

class OrderPolicy
{
    public function viewAsProvider(Provider $provider, Order $order): bool
    {
        if ($order->provider_id === $provider->id) {
            return true;
        }

        if ($order->offers()->where('provider_id', $provider->id)->exists()) {
            return true;
        }

        if (
            $order->status->is(OrderStatusEnum::New)
            && $order->accepted_offer_id === null
            && $provider->providerCategories()->where('category_id', $order->category_id)->exists()
        ) {
            return true;
        }

        return false;
    }
}
