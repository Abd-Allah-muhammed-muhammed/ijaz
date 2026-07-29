<?php

namespace Modules\Payment\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Payment\Actions\GetAcceptedDailyTotalsSinceAction;
use Modules\Payment\Actions\HandleCallbackAction;
use Modules\Payment\Actions\HandleRajhiWebhookAction;
use Modules\Payment\Actions\SumAcceptedPaymentsAction;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\Contracts\Repositories\PaymentRepositoryInterface;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use RuntimeException;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $repository,
        private readonly SumAcceptedPaymentsAction $sumAcceptedPaymentsAction,
        private readonly GetAcceptedDailyTotalsSinceAction $acceptedDailyTotalsAction,
        private readonly HandleRajhiWebhookAction $handleRajhiWebhookAction,
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
        $driver = $driver ?? $this->getDefaultDriver();
        $gateway = $this->resolveGateway($driver);

        $payment = $this->repository->createForOwner($owner, [
            'product_type' => $product::class,
            'product_id' => $product->getKey(),
            'amount' => $amount,
            'status' => PaymentStatusEnum::Pending,
            'driver' => $driver,
        ]);

        return $gateway->initiate($payment);
    }

    /**
     * Resolve the gateway instance by driver name.
     */
    public function resolveGateway(string $driver): PaymentGatewayInterface
    {
        $gateways = config('payment.gateways', []);

        if (! array_key_exists($driver, $gateways)) {
            throw new RuntimeException("Unsupported payment driver: [{$driver}]");
        }

        return app($gateways[$driver]);
    }

    /**
     * Return the default driver from config.
     */
    public function getDefaultDriver(): string
    {
        return config('payment.default', 'paytabs');
    }

    /**
     * Gateway redirect/IPN/testing checkout callback path.
     * Resolves HandleCallbackAction via the container to avoid a constructor
     * cycle (Action needs this Service for resolveGateway).
     */
    public function handleCallback(Payment $payment, array $payload): Payment
    {
        app(HandleCallbackAction::class)->handle($payment, $payload);

        return $this->repository->refresh($payment);
    }

    public function handleRajhiWebhook(array $payload): void
    {
        $this->handleRajhiWebhookAction->handle($payload);
    }

    public function sumAcceptedAmount(): float|int|string
    {
        return $this->sumAcceptedPaymentsAction->handle();
    }

    /**
     * @return Collection<string, float|int|string>
     */
    public function acceptedDailyTotalsSince(CarbonInterface $since): Collection
    {
        return $this->acceptedDailyTotalsAction->handle($since);
    }
}
