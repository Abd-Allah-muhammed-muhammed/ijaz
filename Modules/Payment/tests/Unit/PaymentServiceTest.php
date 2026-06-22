<?php

use Illuminate\Support\Facades\DB;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Gateways\PayTabsGateway;
use Modules\Payment\Gateways\TestingGateway;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;
use Modules\Wallet\Models\TopUpRequest;

test('initiate creates Payment record with correct fields', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();

    DB::transaction(function () use ($user, $topUp) {
        app(PaymentService::class)->initiate($user, $topUp, 150.00, 'testing');
    });

    $payment = Payment::query()->first();

    expect($payment)->not->toBeNull()
        ->and($payment->user_id)->toBe($user->id)
        ->and($payment->user_type)->toBe($user::class)
        ->and($payment->product_type)->toBe(TopUpRequest::class)
        ->and($payment->product_id)->toBe($topUp->id)
        ->and((float) $payment->amount)->toBe(150.0)
        ->and($payment->status)->toBe(PaymentStatusEnum::Pending)
        ->and($payment->driver)->toBe('testing');
});

test('initiate returns PaymentInitResult', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();

    $result = DB::transaction(fn () => app(PaymentService::class)->initiate($user, $topUp, 100.00, 'testing'));

    expect($result)->toBeInstanceOf(PaymentInitResult::class)
        ->and($result->isSuccessful())->toBeTrue()
        ->and($result->driver)->toBe('testing')
        ->and($result->payable)->toBeTrue();
});

test('initiate uses default driver from config when no driver passed', function () {
    config(['payment.default' => 'testing']);

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();

    DB::transaction(fn () => app(PaymentService::class)->initiate($user, $topUp, 75.00));

    expect(Payment::query()->first()->driver)->toBe('testing');
});

test('initiate uses passed driver', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();

    DB::transaction(fn () => app(PaymentService::class)->initiate($user, $topUp, 75.00, 'testing'));

    expect(Payment::query()->first()->driver)->toBe('testing');
});

test('resolveGateway returns TestingGateway for testing driver', function () {
    $gateway = app(PaymentService::class)->resolveGateway('testing');

    expect($gateway)->toBeInstanceOf(TestingGateway::class);
});

test('resolveGateway returns PayTabsGateway for paytabs driver', function () {
    $gateway = app(PaymentService::class)->resolveGateway('paytabs');

    expect($gateway)->toBeInstanceOf(PayTabsGateway::class);
});

test('resolveGateway throws RuntimeException for unknown driver', function () {
    expect(fn () => app(PaymentService::class)->resolveGateway('unknown-driver'))
        ->toThrow(RuntimeException::class, 'Unsupported payment driver: [unknown-driver]');
});

test('getDefaultDriver reads from config', function () {
    config(['payment.default' => 'testing']);

    expect(app(PaymentService::class)->getDefaultDriver())->toBe('testing');
});
