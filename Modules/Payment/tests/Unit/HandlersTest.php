<?php

use App\Enums\OperationStatusEnum;
use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Models\OrderOffer;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Handlers\GuarantorPaymentHandler;
use Modules\Payment\Handlers\OrderPaymentHandler;
use Modules\Payment\Handlers\TopUpPaymentHandler;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Services\WalletService;

test('OrderPaymentHandler onSuccess marks offer as Paid', function () {
    ['user' => $user, 'offer' => $offer] = createOrderPaymentContext();
    $payment = createPaymentFor($user, $offer, ['amount' => 500, 'driver' => 'testing']);

    DB::transaction(fn () => app(OrderPaymentHandler::class)->onSuccess($payment));

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Paid);
});

test('OrderPaymentHandler onSuccess marks order as InProgress', function () {
    ['user' => $user, 'order' => $order, 'offer' => $offer] = createOrderPaymentContext();
    $payment = createPaymentFor($user, $offer, ['amount' => 500, 'driver' => 'testing']);

    DB::transaction(fn () => app(OrderPaymentHandler::class)->onSuccess($payment));

    expect($order->fresh()->status)->toBe(OrderStatusEnum::InProgress);
});

test('OrderPaymentHandler onSuccess sets order price from payment amount', function () {
    ['user' => $user, 'order' => $order, 'offer' => $offer] = createOrderPaymentContext(400);
    $payment = createPaymentFor($user, $offer, ['amount' => 425, 'driver' => 'testing']);

    DB::transaction(fn () => app(OrderPaymentHandler::class)->onSuccess($payment));

    expect((float) $order->fresh()->price)->toBe(425.0);
});

test('OrderPaymentHandler onSuccess calls walletService addPendingDebit for user', function () {
    ['user' => $user, 'offer' => $offer] = createOrderPaymentContext();
    $payment = createPaymentFor($user, $offer, ['amount' => 500, 'driver' => 'testing']);

    $walletService = Mockery::mock(WalletService::class);
    $walletService->shouldReceive('addPendingDebit')->once()->with(
        Mockery::type(User::class),
        500.0,
        Mockery::type(OrderOffer::class),
        Mockery::type('string'),
    );
    $walletService->shouldReceive('adjustPending')->once();

    $handler = new OrderPaymentHandler($walletService);

    DB::transaction(fn () => $handler->onSuccess($payment));
});

test('OrderPaymentHandler onSuccess calls walletService adjustPending for provider', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderPaymentContext();
    $payment = createPaymentFor($user, $offer, ['amount' => 500, 'driver' => 'testing']);

    $walletService = Mockery::mock(WalletService::class);
    $walletService->shouldReceive('addPendingDebit')->once();
    $walletService->shouldReceive('adjustPending')->once()->with(
        Mockery::type(Provider::class),
        500.0,
        50.0,
        Mockery::type(OrderOffer::class),
        Mockery::type('string'),
    );

    $handler = new OrderPaymentHandler($walletService);

    DB::transaction(fn () => $handler->onSuccess($payment));
});

test('OrderPaymentHandler onFailure does nothing', function () {
    ['user' => $user, 'offer' => $offer] = createOrderPaymentContext();
    $payment = createPaymentFor($user, $offer, ['amount' => 500, 'driver' => 'testing']);
    $originalStatus = $offer->status;

    app(OrderPaymentHandler::class)->onFailure($payment);

    expect($offer->fresh()->status)->toBe($originalStatus);
});

test('OrderPaymentHandler productTypes returns OrderOffer class', function () {
    expect(app(OrderPaymentHandler::class)->productTypes())->toBe([OrderOffer::class]);
});

test('TopUpPaymentHandler onSuccess approves TopUpRequest', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, [
        'amount' => 200,
        'driver' => 'testing',
        'transaction_id' => 'topup-txn-1',
    ]);

    DB::transaction(fn () => app(TopUpPaymentHandler::class)->onSuccess($payment));

    expect($topUp->fresh()->status)->toBe(OperationStatusEnum::Approved);
});

test('TopUpPaymentHandler onSuccess credits wallet', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, [
        'amount' => 200,
        'driver' => 'testing',
        'transaction_id' => 'topup-txn-2',
    ]);

    DB::transaction(fn () => app(TopUpPaymentHandler::class)->onSuccess($payment));

    expect((float) $user->wallet->fresh()->balance)->toBe(200.0);
});

test('TopUpPaymentHandler onSuccess sets payment_driver and transaction_id on TopUpRequest', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, [
        'amount' => 150,
        'driver' => 'testing',
        'transaction_id' => 'topup-txn-3',
    ]);

    DB::transaction(fn () => app(TopUpPaymentHandler::class)->onSuccess($payment));

    $topUp->refresh();

    expect($topUp->transaction_id)->toBe('topup-txn-3')
        ->and($topUp->payment_driver)->toBe('testing')
        ->and($topUp->payment_status)->toBe(PaymentStatusEnum::Accepted);
});

test('TopUpPaymentHandler onFailure rejects TopUpRequest', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['amount' => 100, 'driver' => 'testing']);

    app(TopUpPaymentHandler::class)->onFailure($payment);

    expect($topUp->fresh()->payment_status)->toBe(PaymentStatusEnum::Rejected);
});

test('TopUpPaymentHandler productTypes returns TopUpRequest class', function () {
    expect(app(TopUpPaymentHandler::class)->productTypes())->toBe([TopUpRequest::class]);
});

test('GuarantorPaymentHandler onSuccess does nothing — delegates to event listener', function () {
    $user = createWalletUser();
    $request = GuarantorRequest::factory()->accepted()->create();
    $payment = createPaymentFor($user, $request, ['amount' => 1000, 'driver' => 'testing']);
    $originalStatus = $request->status;

    app(GuarantorPaymentHandler::class)->onSuccess($payment);

    expect($request->fresh()->status)->toBe($originalStatus);
});

test('GuarantorPaymentHandler onFailure does nothing — delegates to event listener', function () {
    $user = createWalletUser();
    $request = GuarantorRequest::factory()->accepted()->create();
    $payment = createPaymentFor($user, $request, ['amount' => 1000, 'driver' => 'testing']);
    $originalStatus = $request->status;

    app(GuarantorPaymentHandler::class)->onFailure($payment);

    expect($request->fresh()->status)->toBe($originalStatus);
});

test('GuarantorPaymentHandler productTypes returns GuarantorRequest and GuarantorInstallment', function () {
    expect(app(GuarantorPaymentHandler::class)->productTypes())->toBe([
        GuarantorRequest::class,
        GuarantorInstallment::class,
    ]);
});
