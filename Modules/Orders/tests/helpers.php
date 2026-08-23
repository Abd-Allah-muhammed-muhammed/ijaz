<?php

use App\Models\Provider;
use App\Models\User;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;

/**
 * @return array{user: User, provider: Provider, order: Order, offer: OrderOffer}
 */
function paidInProgressOrder(float $price = 500.0): array
{
    $context = createOrderPaymentContext($price);

    $payment = createPaymentFor($context['user'], $context['offer'], [
        'amount' => $price,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment));

    return [
        ...$context,
        'order' => $context['order']->fresh(),
    ];
}

/**
 * @return array{user: User, provider: Provider, order: Order, offer: OrderOffer}
 */
function paidEndedOrder(float $price = 500.0, OrderStatusEnum $endedStatus = OrderStatusEnum::EndedByClient): array
{
    $context = paidInProgressOrder($price);

    $order = $context['order']->fresh();
    $order->update(['status' => $endedStatus]);

    return [
        ...$context,
        'order' => $order->fresh(),
    ];
}
