<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;
use Modules\Wallet\Models\TopUpRequest;

test('testing gateway initiate returns checkout page url, not immediate verify', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();

    $result = DB::transaction(
        fn () => app(PaymentService::class)->initiate($user, $topUp, 100.00, 'testing')
    );

    $payment = Payment::query()->first();

    expect($result->url)
        ->toBe(route('payment.testing.checkout', ['payment' => $payment->id]))
        ->and($result->url)->not->toContain('/redirect')
        ->and($payment->status)->toBe(PaymentStatusEnum::Pending)
        ->and($payment->url)->toBe($result->url)
        ->and($payment->request['checkout_url'] ?? null)->toBe($result->url);
});

test('testing checkout page renders for testing driver payment', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 75.5]);

    $this->get(route('payment.testing.checkout', $payment))
        ->assertSuccessful()
        ->assertSee('TEST MODE')
        ->assertSee('75.50')
        ->assertSee('Simulate Success')
        ->assertSee('Simulate Failure')
        ->assertSee('Simulate Cancellation');
});

test('testing checkout page returns 404 for non-testing driver payment', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'paytabs', 'amount' => 100]);

    $this->get(route('payment.testing.checkout', $payment))
        ->assertNotFound();
});

test('testing checkout page returns 404 in production environment', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    $this->app['env'] = 'production';

    $this->get(route('payment.testing.checkout', $payment))
        ->assertNotFound();
});

test('completing checkout with success status fires PaymentCompleted event', function () {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    $this->post(route('payment.testing.checkout.complete', $payment), [
        'status' => 'success',
    ])->assertRedirect(route('payment.redirect', [
        'driver' => 'testing',
        'payment' => $payment,
    ]));

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::Accepted);

    Event::assertDispatched(PaymentCompleted::class, fn (PaymentCompleted $event) => $event->payment->id === $payment->id);
    Event::assertNotDispatched(PaymentFailed::class);
});

test('completing checkout with failed status fires PaymentFailed event', function () {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    $this->post(route('payment.testing.checkout.complete', $payment), [
        'status' => 'failed',
    ])->assertRedirect();

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::Rejected);

    Event::assertDispatched(PaymentFailed::class, fn (PaymentFailed $event) => $event->payment->id === $payment->id);
    Event::assertNotDispatched(PaymentCompleted::class);
});

test('completing checkout with cancelled status sets payment to Canceled', function () {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    $this->post(route('payment.testing.checkout.complete', $payment), [
        'status' => 'cancelled',
    ])->assertRedirect();

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::Canceled);

    Event::assertDispatched(PaymentFailed::class);
    Event::assertNotDispatched(PaymentCompleted::class);
});

test('completing checkout redirects through the UX-only redirect route', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    $this->post(route('payment.testing.checkout.complete', $payment), [
        'status' => 'success',
    ])->assertRedirect(route('payment.redirect', [
        'driver' => 'testing',
        'payment' => $payment,
    ]));

    $this->get(route('payment.redirect', [
        'driver' => 'testing',
        'payment' => $payment,
    ]))->assertRedirect(route('payment.success', $payment));
});

test('redirect route after testing checkout does not fire duplicate events', function () {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['driver' => 'testing', 'amount' => 100]);

    $this->post(route('payment.testing.checkout.complete', $payment), [
        'status' => 'success',
    ])->assertRedirect();

    Event::assertDispatchedTimes(PaymentCompleted::class, 1);

    $this->get(route('payment.redirect', [
        'driver' => 'testing',
        'payment' => $payment,
    ]))->assertRedirect(route('payment.success', $payment));

    Event::assertDispatchedTimes(PaymentCompleted::class, 1);
});
