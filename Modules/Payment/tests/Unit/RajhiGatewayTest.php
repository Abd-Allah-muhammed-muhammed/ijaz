<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Modules\Payment\Actions\HandleRajhiCallbackAction;
use Modules\Payment\Actions\HandleRajhiWebhookAction;
use Modules\Payment\Actions\InitiateRajhiPaymentAction;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Services\RajhiEncryptionService;
use Modules\Wallet\Models\TopUpRequest;

beforeEach(function () {
    configureRajhiTestCredentials();
    forgetRajhiServices();
});

// RajhiEncryptionService
test('encrypts and decrypts array roundtrip correctly', function () {
    $plain = ['id' => 'test123', 'amt' => '100.00', 'action' => '1'];

    $encrypted = rajhiEncryptionService()->encrypt($plain);
    $decrypted = rajhiEncryptionService()->decrypt($encrypted);

    expect($decrypted)->toBe($plain);
});

test('decrypt throws on invalid hex trandata', function () {
    expect(fn () => rajhiEncryptionService()->decrypt('ABC'))
        ->toThrow(RuntimeException::class, 'invalid hex trandata');
});

test('decrypt throws on invalid key', function () {
    $encrypted = rajhiEncryptionService()->encrypt(['foo' => 'bar']);

    config(['payment.drivers.rajhi.test.resource_key' => str_repeat('b', 32)]);

    expect(fn () => (new RajhiEncryptionService)->decrypt($encrypted))
        ->toThrow(RuntimeException::class, 'Rajhi decryption failed');
});

// InitiateRajhiPaymentAction
test('initiate returns failed result when Neoleap returns non-200', function () {
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $result = app(InitiateRajhiPaymentAction::class)->handle($payment);

    expect($result->isSuccessful())->toBeFalse()
        ->and($result->driver)->toBe('rajhi')
        ->and($result->message)->toContain('Neoleap gateway error: 500');
});

test('initiate returns failed result when Neoleap status is not 1', function () {
    Http::fake([
        '*' => Http::response([['result' => 'error', 'status' => '0']], 200),
    ]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $result = app(InitiateRajhiPaymentAction::class)->handle($payment);

    expect($result->isSuccessful())->toBeFalse()
        ->and($result->message)->toContain('Neoleap rejected the request');
});

test('initiate parses PaymentID and builds redirect URL correctly', function () {
    Http::fake([
        '*' => Http::response([[
            'result' => '600202533178503778:https://securepayments.neoleap.com.sa/pg/paymentpage.htm',
            'status' => '1',
        ]], 200),
    ]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $result = app(InitiateRajhiPaymentAction::class)->handle($payment);

    expect($result->isSuccessful())->toBeTrue()
        ->and($result->driver)->toBe('rajhi')
        ->and($result->transactionId)->toBe('600202533178503778')
        ->and($result->url)->toBe('https://securepayments.neoleap.com.sa/pg/paymentpage.htm?PaymentID=600202533178503778')
        ->and($result->payable)->toBeTrue();
});

test('initiate returns failed result on connection exception', function () {
    Http::fake(function () {
        throw new ConnectionException('Connection timed out');
    });

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $result = app(InitiateRajhiPaymentAction::class)->handle($payment);

    expect($result->isSuccessful())->toBeFalse()
        ->and($result->message)->toContain('Connection error: Connection timed out');
});

// HandleRajhiCallbackAction
test('handles encrypted trandata and maps CAPTURED to Accepted', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $trandata = rajhiTrandata(['result' => 'CAPTURED', 'transId' => 'rajhi-txn-1']);

    $result = app(HandleRajhiCallbackAction::class)->handle($payment, ['trandata' => $trandata]);

    expect($result->status)->toBe(PaymentStatusEnum::Accepted)
        ->and($result->transactionId)->toBe('rajhi-txn-1')
        ->and($result->isAccepted())->toBeTrue()
        ->and($result->rawResponse)->toHaveKey('trandata');
});

test('handles encrypted trandata and maps NOT CAPTURED to Rejected', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $trandata = rajhiTrandata(['result' => 'NOT CAPTURED', 'transId' => 'rajhi-txn-2']);

    $result = app(HandleRajhiCallbackAction::class)->handle($payment, ['trandata' => $trandata]);

    expect($result->status)->toBe(PaymentStatusEnum::Rejected);
});

test('handles encrypted trandata and maps VOIDED to Canceled', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $trandata = rajhiTrandata(['result' => 'VOIDED', 'transId' => 'rajhi-txn-3']);

    $result = app(HandleRajhiCallbackAction::class)->handle($payment, ['trandata' => $trandata]);

    expect($result->status)->toBe(PaymentStatusEnum::Canceled);
});

test('handles encrypted trandata and maps HOST TIMEOUT to Pending', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $trandata = rajhiTrandata(['result' => 'HOST TIMEOUT', 'transId' => 'rajhi-txn-4']);

    $result = app(HandleRajhiCallbackAction::class)->handle($payment, ['trandata' => $trandata]);

    expect($result->status)->toBe(PaymentStatusEnum::Pending);
});

test('falls back to direct fields when trandata is missing', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $result = app(HandleRajhiCallbackAction::class)->handle($payment, [
        'result' => 'CAPTURED',
        'transId' => 'rajhi-direct-1',
    ]);

    expect($result->status)->toBe(PaymentStatusEnum::Accepted)
        ->and($result->transactionId)->toBe('rajhi-direct-1');
});

test('maps authRespCode to code and description in message', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $result = app(HandleRajhiCallbackAction::class)->handle($payment, [
        'result' => 'NOT CAPTURED',
        'transId' => 'rajhi-n7-1',
        'authRespCode' => 'N7',
    ]);

    expect($result->status)->toBe(PaymentStatusEnum::Rejected)
        ->and($result->message)->toBe('N7 — CVV2/CVC2 mismatch — incorrect CVV entered');
});

test('returns Rejected when decryption fails', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $result = app(HandleRajhiCallbackAction::class)->handle($payment, [
        'trandata' => str_repeat('00', 32),
    ]);

    expect($result->status)->toBe(PaymentStatusEnum::Rejected)
        ->and($result->message)->toContain('Decryption failed');
});

// HandleRajhiWebhookAction
test('throws when trackId is missing from payload', function () {
    expect(fn () => app(HandleRajhiWebhookAction::class)->handle([]))
        ->toThrow(RuntimeException::class, 'Rajhi webhook: missing trackId');
});

test('throws when payment not found', function () {
    expect(fn () => app(HandleRajhiWebhookAction::class)->handle(rajhiWebhookPayload('missing-payment-id')))
        ->toThrow(RuntimeException::class, 'Rajhi webhook: payment not found');
});

test('returns early when payment is already processed (idempotency)', function () {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100, [
        'status' => PaymentStatusEnum::Accepted,
        'transaction_id' => 'already-done',
    ]);

    app(HandleRajhiWebhookAction::class)->handle(rajhiWebhookPayload(
        $payment->id,
        'CAPTURED',
        ['transId' => 'should-not-apply'],
    ));

    expect($payment->fresh()->transaction_id)->toBe('already-done');
    Event::assertNothingDispatched();
});

test('processes payment and fires PaymentCompleted on CAPTURED', function () {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    app(HandleRajhiWebhookAction::class)->handle(rajhiWebhookPayload(
        $payment->id,
        'CAPTURED',
        ['transId' => 'webhook-captured-1'],
    ));

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatusEnum::Accepted)
        ->and($payment->transaction_id)->toBe('webhook-captured-1');

    Event::assertDispatched(PaymentCompleted::class, fn (PaymentCompleted $event) => $event->payment->id === $payment->id);
    Event::assertNotDispatched(PaymentFailed::class);
});

test('processes payment and fires PaymentFailed on NOT CAPTURED', function () {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    app(HandleRajhiWebhookAction::class)->handle(rajhiWebhookPayload(
        $payment->id,
        'NOT CAPTURED',
        ['transId' => 'webhook-failed-1'],
    ));

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatusEnum::Rejected);

    Event::assertDispatched(PaymentFailed::class, fn (PaymentFailed $event) => $event->payment->id === $payment->id);
    Event::assertNotDispatched(PaymentCompleted::class);
});

test('webhook parses nested payLoad structure per ARB spec', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    app(HandleRajhiWebhookAction::class)->handle(rajhiWebhookPayload($payment->id));

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::Accepted)
        ->and($payment->fresh()->transaction_id)->toBe('202110527755152');
});

test('webhook reads top-level result status correctly', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $payload = rajhiWebhookPayload($payment->id, 'CAPTURED');
    $payload[0]['payLoad'][0]['result'] = 'NOT CAPTURED';

    app(HandleRajhiWebhookAction::class)->handle($payload);

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::Accepted);
});

test('webhook handles PAYMENT FAILURE type with NOT CAPTURED result', function () {
    Event::fake([PaymentCompleted::class, PaymentFailed::class]);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createRajhiPaymentFor($user, $topUp, 100);

    $payload = rajhiWebhookPayload($payment->id, 'NOT CAPTURED');
    $payload[0]['type'] = 'PAYMENT FAILURE';

    app(HandleRajhiWebhookAction::class)->handle($payload);

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::Rejected);
    Event::assertDispatched(PaymentFailed::class);
});
