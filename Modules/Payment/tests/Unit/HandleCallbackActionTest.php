<?php

use App\Models\User;
use Illuminate\Support\Facades\Event;
use Modules\Payment\Actions\HandleCallbackAction;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Modules\Wallet\Models\TopUpRequest;

test('skips processing when payment status is not Pending — idempotency', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, [
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
        'transaction_id' => 'existing-txn',
    ]);

    app(HandleCallbackAction::class)->handle($payment, ['status' => 'success']);

    expect($payment->fresh()->transaction_id)->toBe('existing-txn');
});

test('updates payment status to Accepted on success', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    app(HandleCallbackAction::class)->handle($payment, [
        'status' => 'success',
        'payment_id' => 'txn-accepted-1',
    ]);

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatusEnum::Accepted)
        ->and($payment->transaction_id)->toBe('txn-accepted-1');
});

test('updates payment status to Rejected on failure', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    app(HandleCallbackAction::class)->handle($payment, [
        'status' => 'failed',
        'payment_id' => 'txn-rejected-1',
    ]);

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatusEnum::Rejected)
        ->and($payment->transaction_id)->toBe('txn-rejected-1');
});

test('fires PaymentCompleted event after transaction when accepted', function () {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    app(HandleCallbackAction::class)->handle($payment, [
        'status' => 'success',
        'payment_id' => 'txn-event-success',
    ]);

    Event::assertDispatched(PaymentCompleted::class, fn (PaymentCompleted $event) => $event->payment->id === $payment->id);
    Event::assertNotDispatched(PaymentFailed::class);
});

test('fires PaymentFailed event after transaction when rejected', function () {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    app(HandleCallbackAction::class)->handle($payment, [
        'status' => 'failed',
        'payment_id' => 'txn-event-failure',
    ]);

    Event::assertDispatched(PaymentFailed::class, fn (PaymentFailed $event) => $event->payment->id === $payment->id);
    Event::assertNotDispatched(PaymentCompleted::class);
});

test('does not fire events when payment already processed — idempotency', function () {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, [
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
        'transaction_id' => 'already-done',
    ]);

    app(HandleCallbackAction::class)->handle($payment, ['status' => 'success']);

    Event::assertNothingDispatched();
});

test('does not throw when product type has no listener', function () {
    $user = createWalletUser();
    $payment = createPaymentFor($user, $user, [
        'driver' => 'testing',
        'product_type' => User::class,
        'product_id' => (string) $user->id,
    ]);

    app(HandleCallbackAction::class)->handle($payment, [
        'status' => 'success',
        'payment_id' => 'txn-no-listener',
    ]);

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::Accepted);
});
