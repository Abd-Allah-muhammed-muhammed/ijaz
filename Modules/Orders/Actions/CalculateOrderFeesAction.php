<?php

namespace Modules\Orders\Actions;

use Modules\Orders\DTOs\OrderFeesResult;
use Modules\Orders\Models\Order;
use Modules\Payment\Services\PaymentService;

/**
 * Single source of truth for order offer fee calculation.
 *
 * Canonical gateway key: PaymentService::getDefaultDriver().'_fees'
 *
 * Why not bare config('payment.default')?
 * PaymentService::getDefaultDriver() returns config('payment.default', 'paytabs') —
 * the same config key, but with an explicit 'paytabs' fallback when the config is
 * unset. Previously User accept used getDefaultDriver() and Provider update used
 * config('payment.default') directly; those only diverge when config is missing or
 * getDefaultDriver is mocked/overridden. Using PaymentService keeps fee resolution
 * on the payment domain API and avoids dual-source fragility.
 */
class CalculateOrderFeesAction
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function handle(Order $order, float $offerPrice): OrderFeesResult
    {
        $categoryFees = $order->category->getFees($offerPrice);
        $gatewayFeesKey = $this->paymentService->getDefaultDriver().'_fees';
        $gatewayFees = (float) app('settings')->get($gatewayFeesKey, 0);
        $providerFees = $gatewayFees + $categoryFees + (0.15 * $categoryFees);

        return new OrderFeesResult(
            price: $offerPrice,
            providerFees: $providerFees,
            userFees: 0.0,
        );
    }
}
