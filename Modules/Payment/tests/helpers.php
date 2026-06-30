<?php

use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Models\Order;
use App\Models\OrderOffer;
use App\Models\Provider;
use App\Models\User;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Payment\Actions\HandleRajhiCallbackAction;
use Modules\Payment\Actions\HandleRajhiWebhookAction;
use Modules\Payment\Actions\InitiateRajhiPaymentAction;
use Modules\Payment\Contracts\PaymentGatewayInterface;
use Modules\Payment\DTOs\PaymentVerifyResult;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Gateways\PayTabsGateway;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\RajhiEncryptionService;

function createPaymentFor(Model $owner, Model $product, array $attributes = []): Payment
{
    return Payment::factory()
        ->forProduct($product, $owner)
        ->create($attributes);
}

/**
 * @return array{user: User, provider: Provider, order: Order, offer: OrderOffer}
 */
function createOrderPaymentContext(float $price = 500.0): array
{
    $user = createWalletUser();
    $provider = createWalletProvider();
    $category = CategoryFactory::new()->create(['icon' => 'media/test-category.png']);

    $order = Order::query()->create([
        'title' => 'Test Order',
        'description' => 'Test order description',
        'budget_start' => 400,
        'budget_end' => 600,
        'user_id' => $user->id,
        'provider_id' => $provider->id,
        'category_id' => $category->id,
        'price' => $price,
        'status' => OrderStatusEnum::OfferProvided,
        'user_fees' => 25,
        'provider_fees' => 50,
    ]);

    $offer = OrderOffer::query()->create([
        'order_id' => $order->id,
        'user_id' => $user->id,
        'provider_id' => $provider->id,
        'category_id' => $category->id,
        'price' => $price,
        'description' => 'Test offer',
        'status' => OfferStatusEnum::Accepted,
    ]);

    $order->update(['accepted_offer_id' => $offer->id]);

    return compact('user', 'provider', 'order', 'offer');
}

function mockPayTabsGateway(PaymentVerifyResult $result): void
{
    $gateway = Mockery::mock(PaymentGatewayInterface::class);
    $gateway->shouldReceive('verify')->andReturn($result);

    app()->instance(PayTabsGateway::class, $gateway);
}

function configureRajhiTestCredentials(): void
{
    config([
        'payment.drivers.rajhi.mode' => 'test',
        'payment.drivers.rajhi.test.resource_key' => str_repeat('a', 32),
        'payment.drivers.rajhi.test.encryption_iv' => 'PGKEYENCDECIVSPC',
        'payment.drivers.rajhi.test.tranportal_id' => 'test-portal-id',
        'payment.drivers.rajhi.test.tranportal_password' => 'test-portal-password',
        'payment.drivers.rajhi.test.currency' => '682',
        'payment.drivers.rajhi.test.endpoint' => 'https://securepayments.neoleap.com.sa/pg/payment/hosted.htm',
    ]);
}

function forgetRajhiServices(): void
{
    app()->forgetInstance(RajhiEncryptionService::class);
    app()->forgetInstance(InitiateRajhiPaymentAction::class);
    app()->forgetInstance(HandleRajhiCallbackAction::class);
    app()->forgetInstance(HandleRajhiWebhookAction::class);
}

function rajhiEncryptionService(): RajhiEncryptionService
{
    return new RajhiEncryptionService;
}

function createRajhiPaymentFor(Model $owner, Model $product, float $amount = 100.0, array $attributes = []): Payment
{
    return createPaymentFor($owner, $product, array_merge([
        'driver' => 'rajhi',
        'amount' => $amount,
        'status' => PaymentStatusEnum::Pending,
    ], $attributes));
}

function rajhiTrandata(array $payload): string
{
    return rajhiEncryptionService()->encrypt($payload);
}

/**
 * @return array<int, array<string, mixed>>
 */
function rajhiWebhookPayload(string $trackId, string $resultStatus = 'CAPTURED', array $payLoadOverrides = []): array
{
    return [
        [
            'result' => [['status' => $resultStatus]],
            'responseURL' => 'https://example.com/webhook',
            'payLoad' => [[
                'date' => '0415',
                'authRespCode' => $resultStatus === 'CAPTURED' ? '00' : 'N7',
                'authCode' => '623666',
                'transId' => 202110527755152,
                'trackId' => $trackId,
                'amt' => 10,
                'actionCode' => '1',
                'card' => '401200XXXXXX1112',
                'expMonth' => '6',
                'expYear' => '24',
                ...$payLoadOverrides,
            ]],
            'type' => 'PAYMENT',
        ],
    ];
}
