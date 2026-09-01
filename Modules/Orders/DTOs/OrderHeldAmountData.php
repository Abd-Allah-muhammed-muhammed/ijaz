<?php

namespace Modules\Orders\DTOs;

use Illuminate\Database\Eloquent\Model;
use Modules\Orders\Models\Order;

final readonly class OrderHeldAmountData
{
    public function __construct(
        public float $gross,
        public float $fee,
        public float $net,
        public Model $operation,
    ) {}

    public static function fromOrder(Order $order): self
    {
        $order->loadMissing('acceptedOffer');

        $gross = (float) $order->price;
        $fee = (float) $order->total_fees;
        $net = $gross - (float) $order->provider_fees;
        $operation = $order->acceptedOffer ?? $order;

        return new self(
            gross: $gross,
            fee: $fee,
            net: $net,
            operation: $operation,
        );
    }
}
