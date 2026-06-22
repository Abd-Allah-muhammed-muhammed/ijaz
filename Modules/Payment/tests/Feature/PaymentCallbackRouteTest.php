<?php

use Modules\Payment\DTOs\PaymentVerifyResult;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Wallet\Models\TopUpRequest;

test('redirect processes accepted payment and redirects to success', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    $this->get(route('payment.redirect', [
        'driver' => 'testing',
        'payment' => $payment->id,
        'status' => 'success',
        'payment_id' => 'redirect-txn-1',
    ]))->assertRedirect(route('payment.success', $payment));

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::Accepted);
});

test('redirect processes rejected payment and redirects to failed', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    $this->get(route('payment.redirect', [
        'driver' => 'testing',
        'payment' => $payment->id,
        'status' => 'failed',
        'payment_id' => 'redirect-txn-2',
    ]))->assertRedirect(route('payment.failed', $payment));

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::Rejected);
});

test('redirect is idempotent — second call does not reprocess', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    $payload = [
        'driver' => 'testing',
        'payment' => $payment->id,
        'status' => 'success',
        'payment_id' => 'redirect-txn-3',
    ];

    $this->get(route('payment.redirect', $payload))->assertRedirect(route('payment.success', $payment));

    $balanceAfterFirst = (float) $user->wallet->fresh()->balance;

    $this->get(route('payment.redirect', $payload))->assertRedirect(route('payment.success', $payment));

    expect((float) $user->wallet->fresh()->balance)->toBe($balanceAfterFirst)
        ->and($payment->fresh()->transaction_id)->toBe('redirect-txn-3');
});

test('redirect works for paytabs driver', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'paytabs', 'amount' => 100]);

    mockPayTabsGateway(new PaymentVerifyResult(
        status: PaymentStatusEnum::Accepted,
        transactionId: 'paytabs-txn-1',
        rawResponse: ['tranRef' => 'paytabs-txn-1'],
    ));

    $this->get(route('payment.redirect', [
        'driver' => 'paytabs',
        'payment' => $payment->id,
        'tranRef' => 'paytabs-txn-1',
    ]))->assertRedirect(route('payment.success', $payment));

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::Accepted);
});

test('redirect works for testing driver', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 50]);

    $this->post(route('payment.redirect', [
        'driver' => 'testing',
        'payment' => $payment->id,
    ]), [
        'status' => 'success',
        'payment_id' => 'testing-txn-1',
    ])->assertRedirect(route('payment.success', $payment));
});

test('callback returns 200 OK on success', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    $this->post(route('payment.callback', [
        'driver' => 'testing',
        'payment' => $payment->id,
    ]), [
        'status' => 'success',
        'payment_id' => 'callback-txn-1',
    ])->assertOk()->assertSee('OK');
});

test('callback returns 200 OK on failure', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    $this->post(route('payment.callback', [
        'driver' => 'testing',
        'payment' => $payment->id,
    ]), [
        'status' => 'failed',
        'payment_id' => 'callback-txn-2',
    ])->assertOk()->assertSee('OK');
});

test('callback is idempotent', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    $url = route('payment.callback', ['driver' => 'testing', 'payment' => $payment->id]);
    $payload = ['status' => 'success', 'payment_id' => 'callback-txn-3'];

    $this->post($url, $payload)->assertOk();
    $balanceAfterFirst = (float) $user->wallet->fresh()->balance;

    $this->post($url, $payload)->assertOk();

    expect((float) $user->wallet->fresh()->balance)->toBe($balanceAfterFirst);
});

test('success page returns 200', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing']);

    $this->get(route('payment.success', $payment))->assertOk();
});

test('failed page returns 200', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing']);

    $this->get(route('payment.failed', $payment))->assertOk();
});
