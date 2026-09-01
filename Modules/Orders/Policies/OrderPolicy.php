<?php

namespace Modules\Orders\Policies;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
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

    public function dispute(Authenticatable $actor, Order $order): bool
    {
        return $this->isParty($actor, $order)
            && $order->status->is(OrderStatusEnum::InProgress);
    }

    public function end(Authenticatable $actor, Order $order): bool
    {
        if ($order->status->is(OrderStatusEnum::Disputed)) {
            return false;
        }

        if ($actor instanceof User && $order->user()->is($actor)) {
            return $order->status->isIn([OrderStatusEnum::InProgress, OrderStatusEnum::EndedByProvider]);
        }

        if ($actor instanceof Provider && $order->provider()->is($actor)) {
            return $order->status->is(OrderStatusEnum::InProgress);
        }

        return false;
    }

    public function cancel(Authenticatable $actor, Order $order): bool
    {
        if ($order->status->is(OrderStatusEnum::Disputed)) {
            return false;
        }

        if ($actor instanceof User && $order->user()->is($actor)) {
            return OrderStatusEnum::isAllowed($order->status, OrderStatusEnum::CancelledByClient, 'user');
        }

        if ($actor instanceof Provider && $order->provider()->is($actor)) {
            return OrderStatusEnum::isAllowed($order->status, OrderStatusEnum::CancelledByProvider, 'provider');
        }

        return false;
    }

    public function chat(Authenticatable $actor, Order $order): bool
    {
        return $this->isParty($actor, $order)
            && $order->status->isIn([
                OrderStatusEnum::PaymentCompleted,
                OrderStatusEnum::InProgress,
                OrderStatusEnum::EndedByProvider,
                OrderStatusEnum::Disputed,
            ]);
    }

    private function isParty(Authenticatable $actor, Order $order): bool
    {
        if ($actor instanceof User) {
            return $order->user()->is($actor);
        }

        if ($actor instanceof Provider) {
            return $order->provider()->is($actor);
        }

        return false;
    }
}
