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

test('online payment option is hidden from Inertia shared data when only the testing driver is configured', function () {
    withoutWalletLocaleMiddleware();
    config(['payment.default' => PaymentDriverEnum::Testing->value]);

    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->get(action([TopUpController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('payment.driver', PaymentDriverEnum::Testing->value)
            ->where('payment.online_enabled', false)
        );
});
