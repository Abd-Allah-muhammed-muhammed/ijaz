<?php

namespace Modules\Payment\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Services\PaymentService;

class InitiatePaymentAction
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function handle(
        Model $owner,
        Model $product,
        float $amount,
        ?string $driver = null,
    ): PaymentInitResult {
        $driver = $driver ?? $this->paymentService->getDefaultDriver();
        $gateway = $this->paymentService->resolveGateway($driver);

        $payment = $owner->payments()->create([
            'product_type' => $product::class,
            'product_id' => $product->getKey(),
            'amount' => $amount,
            'status' => PaymentStatusEnum::Pending,
            'driver' => $driver,
        ]);

        return $gateway->initiate($payment);
    }
}
