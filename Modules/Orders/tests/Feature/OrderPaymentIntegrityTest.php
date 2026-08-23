<?php

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Modules\Orders\Actions\CalculateOrderFeesAction;
use Modules\Orders\Actions\Provider\UpdateProviderOfferAction;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\OrderFeesResult;
use Modules\Orders\DTOs\UpdateOrderOfferDTO;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Listeners\HandleOrderPaymentCompleted;
use Modules\Orders\Models\Order;
use Modules\Orders\Notifications\StuckOrderSettlementsNotification;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;

test('a stale payment (amount no longer matching order.user_total after a provider re-priced the accepted offer) is rejected or reconciled at callback time, not silently applied', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderPaymentContext(500.0);

    $payment = createPaymentFor($user, $offer, [
        'amount' => 500,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    app(UpdateProviderOfferAction::class)->handle(
        $order->fresh(),
        $offer->fresh(),
        $provider,
        UpdateOrderOfferDTO::fromValidated([
            'price' => 600.0,
            'description' => 'Repriced while payment was pending',
        ]),
    );

    DB::transaction(fn () => app(HandleOrderPaymentCompleted::class)->handle(new PaymentCompleted($payment)));

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::NeedsReview)
        ->and($offer->fresh()->status)->not->toBe(OfferStatusEnum::Paid)
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::OfferProvided)
        ->and((float) ($user->wallet?->fresh()?->pending_debit ?? 0))->toBe(0.0)
        ->and((float) ($provider->wallet?->fresh()?->pending_credit ?? 0))->toBe(0.0);
});

test('holds created on payment completion use amount and fees consistent with what was actually charged, even if user_fees becomes non-zero', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderPaymentContext(200.0);

    $fees = new OrderFeesResult(price: 200.0, providerFees: 31.5, userFees: 15.0);
    $expectedTotal = 215.0;

    $calculator = Mockery::mock(CalculateOrderFeesAction::class);
    $calculator->shouldReceive('handle')
        ->with(Mockery::type(Order::class), 200.0)
        ->andReturn($fees);
    app()->instance(CalculateOrderFeesAction::class, $calculator);

    $payment = createPaymentFor($user, $offer, [
        'amount' => $expectedTotal,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment));

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Paid)
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::InProgress)
        ->and((float) $order->fresh()->price)->toBe(200.0)
        ->and((float) $order->fresh()->user_fees)->toBe(15.0)
        ->and((float) $order->fresh()->provider_fees)->toBe(31.5)
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe($expectedTotal)
        ->and((float) $provider->wallet->fresh()->pending_credit)->toBe(200.0)
        ->and((float) $provider->wallet->fresh()->pending_debit)->toBe(-31.5);
});

test('HandleOrderPaymentCompleted does not overwrite order.price with a mismatched payment.amount without recalculating provider_fees to match', function () {
    ['user' => $user, 'order' => $order, 'offer' => $offer] = createOrderPaymentContext(400.0);

    $payment = createPaymentFor($user, $offer, [
        'amount' => 425,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    DB::transaction(fn () => app(HandleOrderPaymentCompleted::class)->handle(new PaymentCompleted($payment)));

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::NeedsReview)
        ->and((float) $order->fresh()->price)->toBe(400.0)
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::OfferProvided)
        ->and($offer->fresh()->status)->not->toBe(OfferStatusEnum::Paid);
});

test('a command/metric surfaces paid+ended orders still unsettled past the dispute window, for ops visibility', function () {
    Notification::fake();
    setWalletSetting('order_dispute_window_hours', '48');
    createOrdersAdmin();

    paidEndedOrder(100.0);

    $this->travel(49)->hours();

    $repository = app(OrderRepositoryInterface::class);
    $endedBefore = now()->subHours(48);

    expect($repository->countDueForWalletSettlement($endedBefore))->toBe(1);

    $warnings = collect();
    Log::listen(function (MessageLogged $event) use ($warnings) {
        if ($event->level === 'warning') {
            $warnings->push($event);
        }
    });

    $this->artisan('orders:alert-unsettled')->assertSuccessful();

    $warning = $warnings->first(fn (MessageLogged $event) => str_contains($event->message, 'Paid ended orders remain unsettled'));

    expect($warning)->not->toBeNull()
        ->and($warning->context['stuck_count'] ?? null)->toBe(1);

    Notification::assertSentTimes(StuckOrderSettlementsNotification::class, 1);
});

test('orders:settle-completed remains idempotent and correct after these changes — full regression of OrderPaymentSettlementTest scenarios', function () {
    setWalletSetting('order_dispute_window_hours', '48');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = paidEndedOrder(500.0);

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $provider->wallet->fresh()->pending_credit)->toBe(500.0)
        ->and($order->wallet_settled_at)->toBeNull();

    $this->travel(49)->hours();

    $this->artisan('orders:settle-completed')->assertSuccessful();

    $gross = (float) $order->fresh()->price;
    $fees = (float) $order->fresh()->provider_fees;
    $net = $gross - $fees;

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $provider->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $provider->wallet->fresh()->balance)->toBe($net)
        ->and($order->fresh()->wallet_settled_at)->not->toBeNull();

    $this->artisan('orders:settle-completed')->assertSuccessful();

    expect($order->fresh()->wallet_settled_at)->not->toBeNull()
        ->and((float) $provider->wallet->fresh()->balance)->toBe($net);
});
