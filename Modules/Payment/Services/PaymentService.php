<?php

namespace Modules\Payment\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Payment\Actions\InitiatePaymentAction;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\Gateways\PayTabsGateway;
use Modules\Payment\Gateways\TestingGateway;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private readonly InitiatePaymentAction $initiatePaymentAction,
    ) {}

    /**
     * Initiate a payment for a product.
     * Must be called inside a DB transaction by the caller.
     */
    public function initiate(
        Model $owner,
        Model $product,
        float $amount,
        ?string $driver = null,
    ): PaymentInitResult {
        return $this->initiatePaymentAction->handle($owner, $product, $amount, $driver);
    }

    /**
     * Resolve the gateway instance by driver name.
     */
    public function resolveGateway(string $driver): PaymentGatewayInterface
    {
        return match ($driver) {
            'paytabs' => app(PayTabsGateway::class),
            'testing' => app(TestingGateway::class),
            default => throw new RuntimeException("Unsupported payment driver: {$driver}"),
        };
    }

    /**
     * Return the default driver from config.
     */
    public function getDefaultDriver(): string
    {
        return config('payment.default', 'paytabs');
    }
}
