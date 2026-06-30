<?php

use Illuminate\Support\Facades\Event;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Modules\Wallet\Models\TopUpRequest;

beforeEach(function () {
    configureRajhiTestCredentials();
    forgetRajhiServices();
});

test('webhook returns status 1 on success', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $this->postJson(route('payment.rajhi.webhook'), rajhiWebhookPayload($payment->id))
        ->assertOk()
        ->assertJson([['status' => '1']]);

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::Accepted);
});

test('webhook returns status 0 when trackId is missing', function () {
    $this->postJson(route('payment.rajhi.webhook'), [
        [
            'result' => [['status' => 'CAPTURED']],
            'payLoad' => [[
                'transId' => '202110527755152',
            ]],
            'type' => 'PAYMENT',
        ],
    ])
        ->assertOk()
        ->assertJson([['status' => '0']]);
});

test('webhook returns status 1 when payment already processed (idempotency)', function () {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100, [
        'status' => PaymentStatusEnum::Accepted,
        'transaction_id' => 'already-processed',
    ]);

    $this->postJson(route('payment.rajhi.webhook'), rajhiWebhookPayload(
        $payment->id,
        'CAPTURED',
        ['transId' => 'should-not-overwrite'],
    ))
        ->assertOk()
        ->assertJson([['status' => '1']]);

    expect($payment->fresh()->transaction_id)->toBe('already-processed');
    Event::assertNothingDispatched();
});

test('webhook fires PaymentCompleted event on CAPTURED result', function () {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $this->postJson(route('payment.rajhi.webhook'), rajhiWebhookPayload($payment->id))->assertOk();

    Event::assertDispatched(PaymentCompleted::class, fn (PaymentCompleted $event) => $event->payment->id === $payment->id);
    Event::assertNotDispatched(PaymentFailed::class);
});

test('webhook fires PaymentFailed event on NOT CAPTURED result', function () {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $this->postJson(route('payment.rajhi.webhook'), rajhiWebhookPayload($payment->id, 'NOT CAPTURED'))->assertOk();

    Event::assertDispatched(PaymentFailed::class, fn (PaymentFailed $event) => $event->payment->id === $payment->id);
    Event::assertNotDispatched(PaymentCompleted::class);
});
