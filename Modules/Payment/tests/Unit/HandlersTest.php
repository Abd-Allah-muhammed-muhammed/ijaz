<?php

use App\Enums\OperationStatusEnum;
use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Listeners\Payment\HandleOrderPaymentCompleted;
use App\Listeners\Payment\HandleOrderPaymentFailed;
use App\Models\OrderOffer;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Listeners\HandleGuarantorPaymentCompleted;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Modules\Wallet\Listeners\HandleTopUpPaymentCompleted;
use Modules\Wallet\Listeners\HandleTopUpPaymentFailed;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Services\WalletService;

test('HandleOrderPaymentCompleted marks offer as Paid', function () {
    ['user' => $user, 'offer' => $offer] = createOrderPaymentContext();
    $payment = createPaymentFor($user, $offer, ['amount' => 500, 'driver' => 'testing', 'status' => PaymentStatusEnum::Accepted]);

    DB::transaction(fn () => app(HandleOrderPaymentCompleted::class)->handle(new PaymentCompleted($payment)));

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Paid);
});

test('HandleOrderPaymentCompleted marks order as InProgress', function () {
    ['user' => $user, 'order' => $order, 'offer' => $offer] = createOrderPaymentContext();
    $payment = createPaymentFor($user, $offer, ['amount' => 500, 'driver' => 'testing', 'status' => PaymentStatusEnum::Accepted]);

    DB::transaction(fn () => app(HandleOrderPaymentCompleted::class)->handle(new PaymentCompleted($payment)));

    expect($order->fresh()->status)->toBe(OrderStatusEnum::InProgress);
});

test('HandleOrderPaymentCompleted sets order price from payment amount', function () {
    ['user' => $user, 'order' => $order, 'offer' => $offer] = createOrderPaymentContext(400);
    $payment = createPaymentFor($user, $offer, ['amount' => 425, 'driver' => 'testing', 'status' => PaymentStatusEnum::Accepted]);

    DB::transaction(fn () => app(HandleOrderPaymentCompleted::class)->handle(new PaymentCompleted($payment)));

    expect((float) $order->fresh()->price)->toBe(425.0);
});

test('HandleOrderPaymentCompleted calls walletService addPendingDebit for user', function () {
    ['user' => $user, 'offer' => $offer] = createOrderPaymentContext();
    $payment = createPaymentFor($user, $offer, ['amount' => 500, 'driver' => 'testing', 'status' => PaymentStatusEnum::Accepted]);

    $walletService = Mockery::mock(WalletService::class);
    $walletService->shouldReceive('addPendingDebit')->once()->with(
        Mockery::type(User::class),
        500.0,
        Mockery::type(OrderOffer::class),
        Mockery::type('string'),
    );
    $walletService->shouldReceive('adjustPending')->once();

    $listener = new HandleOrderPaymentCompleted($walletService);

    DB::transaction(fn () => $listener->handle(new PaymentCompleted($payment)));
});

test('HandleOrderPaymentCompleted calls walletService adjustPending for provider', function () {
    ['user' => $user, 'offer' => $offer] = createOrderPaymentContext();
    $payment = createPaymentFor($user, $offer, ['amount' => 500, 'driver' => 'testing', 'status' => PaymentStatusEnum::Accepted]);

    $walletService = Mockery::mock(WalletService::class);
    $walletService->shouldReceive('addPendingDebit')->once();
    $walletService->shouldReceive('adjustPending')->once()->with(
        Mockery::type(Provider::class),
        500.0,
        50.0,
        Mockery::type(OrderOffer::class),
        Mockery::type('string'),
    );

    $listener = new HandleOrderPaymentCompleted($walletService);

    DB::transaction(fn () => $listener->handle(new PaymentCompleted($payment)));
});

test('HandleOrderPaymentCompleted ignores non-order payments', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['amount' => 100, 'driver' => 'testing', 'status' => PaymentStatusEnum::Accepted]);

    app(HandleOrderPaymentCompleted::class)->handle(new PaymentCompleted($payment));

    expect($topUp->fresh()->status)->toBe(OperationStatusEnum::Pending);
});

test('HandleOrderPaymentFailed does nothing', function () {
    ['user' => $user, 'offer' => $offer] = createOrderPaymentContext();
    $payment = createPaymentFor($user, $offer, ['amount' => 500, 'driver' => 'testing']);
    $originalStatus = $offer->status;

    app(HandleOrderPaymentFailed::class)->handle(new PaymentFailed($payment));

    expect($offer->fresh()->status)->toBe($originalStatus);
});

test('HandleTopUpPaymentCompleted approves TopUpRequest', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, [
        'amount' => 200,
        'driver' => 'testing',
        'transaction_id' => 'topup-txn-1',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    DB::transaction(fn () => app(HandleTopUpPaymentCompleted::class)->handle(new PaymentCompleted($payment)));

    expect($topUp->fresh()->status)->toBe(OperationStatusEnum::Approved);
});

test('HandleTopUpPaymentCompleted credits wallet', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, [
        'amount' => 200,
        'driver' => 'testing',
        'transaction_id' => 'topup-txn-2',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    DB::transaction(fn () => app(HandleTopUpPaymentCompleted::class)->handle(new PaymentCompleted($payment)));

    expect((float) $user->wallet->fresh()->balance)->toBe(200.0);
});

test('HandleTopUpPaymentCompleted sets payment_driver and transaction_id on TopUpRequest', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, [
        'amount' => 150,
        'driver' => 'testing',
        'transaction_id' => 'topup-txn-3',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    DB::transaction(fn () => app(HandleTopUpPaymentCompleted::class)->handle(new PaymentCompleted($payment)));

    $topUp->refresh();

    expect($topUp->transaction_id)->toBe('topup-txn-3')
        ->and($topUp->payment_driver)->toBe('testing')
        ->and($topUp->payment_status)->toBe(PaymentStatusEnum::Accepted);
});

test('HandleTopUpPaymentCompleted ignores non-top-up payments', function () {
    ['user' => $user, 'offer' => $offer] = createOrderPaymentContext();
    $payment = createPaymentFor($user, $offer, ['amount' => 500, 'driver' => 'testing', 'status' => PaymentStatusEnum::Accepted]);

    app(HandleTopUpPaymentCompleted::class)->handle(new PaymentCompleted($payment));

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Accepted);
});

test('HandleTopUpPaymentFailed rejects TopUpRequest', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['amount' => 100, 'driver' => 'testing']);

    app(HandleTopUpPaymentFailed::class)->handle(new PaymentFailed($payment));

    expect($topUp->fresh()->payment_status)->toBe(PaymentStatusEnum::Rejected);
});

test('HandleGuarantorPaymentCompleted processes guarantor request payment', function () {
    $user = createWalletUser();
    $request = GuarantorRequest::factory()->accepted()->create();
    $payment = createPaymentFor($user, $request, [
        'amount' => 1000,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    app(HandleGuarantorPaymentCompleted::class)->handle(new PaymentCompleted($payment));

    expect($request->fresh()->status->value)->toBe('in_progress');
});

test('HandleGuarantorPaymentCompleted ignores non-guarantor payments', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create();
    $payment = createPaymentFor($user, $topUp, ['amount' => 100, 'driver' => 'testing', 'status' => PaymentStatusEnum::Accepted]);

    app(HandleGuarantorPaymentCompleted::class)->handle(new PaymentCompleted($payment));

    expect($topUp->fresh()->status)->toBe(OperationStatusEnum::Pending);
});

test('HandleGuarantorPaymentCompleted handles GuarantorInstallment product type', function () {
    $user = createWalletUser();
    $request = GuarantorRequest::factory()->accepted()->create();
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create();
    $payment = createPaymentFor($user, $installment, [
        'amount' => 500,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    app(HandleGuarantorPaymentCompleted::class)->handle(new PaymentCompleted($payment));

    expect($installment->fresh()->status->value)->toBe('paid');
});
