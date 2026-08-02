<?php

use Modules\Payment\Enums\PaymentDriverEnum;
use Modules\Payment\Enums\PaymentMethodEnum;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;
use Modules\Wallet\Http\Controllers\Provider\TopUpController;
use Modules\Wallet\Models\TopUpRequest;

test('provider top-up recharge always uses the server-configured payment driver, ignoring any client-supplied driver value', function () {
    withoutWalletLocaleMiddleware();
    config(['payment.default' => PaymentDriverEnum::Testing->value]);

    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->postJson(action([TopUpController::class, 'store']), [
            'amount' => 120,
            'payment_method' => PaymentMethodEnum::Online->value,
            'payment_driver' => PaymentDriverEnum::Rajhi->value,
        ])->assertSuccessful();

    $payment = Payment::query()
        ->where('product_type', TopUpRequest::class)
        ->latest('id')
        ->first();

    expect($payment)->not->toBeNull()
        ->and($payment->driver)->toBe(PaymentDriverEnum::Testing->value)
        ->and(app(PaymentService::class)->getDefaultDriver())->toBe(PaymentDriverEnum::Testing->value);
});

test('online payment option shows the testing gateway tile when PAYMENT_DRIVER=testing', function () {
    withoutWalletLocaleMiddleware();
    config(['payment.default' => PaymentDriverEnum::Testing->value]);

    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->get(action([TopUpController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('payment.driver', PaymentDriverEnum::Testing->value)
            ->where('payment.online_enabled', true)
        );
});

test('online payment option is only hidden when no valid driver value is configured', function () {
    withoutWalletLocaleMiddleware();
    config(['payment.default' => 'not-a-real-gateway']);

    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->get(action([TopUpController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('payment.driver', 'not-a-real-gateway')
            ->where('payment.online_enabled', false)
        );
});
