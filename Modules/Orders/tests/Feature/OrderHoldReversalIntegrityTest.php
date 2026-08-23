<?php

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Orders\Actions\CancelOrderPaymentAction;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Http\Controllers\Api\V1\OrderController as UserOrderController;
use Modules\Orders\Notifications\StuckOrderSettlementsNotification;

test('OrderStatusEnum no longer has a Refunded case', function () {
    $values = array_map(fn (OrderStatusEnum $status) => $status->value, OrderStatusEnum::cases());

    expect($values)->not->toContain('refunded');
});

test('the admin orders dashboard status filter no longer offers a Refunded option', function () {
    $source = file_get_contents(resource_path('js/Enums/Order.ts'));

    expect($source)->not->toBeFalse()
        ->and($source)->not->toContain('Refunded: "refunded"');
});

test('settling an order with insufficient holds now surfaces a stronger signal than a log line alone — e.g. included in the existing orders:alert-unsettled / stuckUnsettled reporting', function () {
    Notification::fake();
    setWalletSetting('order_dispute_window_hours', '48');
    createOrdersAdmin();

    ['user' => $user, 'provider' => $provider, 'order' => $order] = paidEndedOrder(500.0);

    $user->wallet->update(['pending_debit' => 100]);
    $provider->wallet->update(['pending_credit' => 100]);

    $this->travel(49)->hours();

    $repository = app(OrderRepositoryInterface::class);
    $endedBefore = now()->subHours(48);

    expect($repository->countDueForWalletSettlement($endedBefore))->toBe(1)
        ->and($repository->dashboardStats()['stuckUnsettled'])->toBe(1);

    $warnings = collect();
    Log::listen(function (MessageLogged $event) use ($warnings) {
        if ($event->level === 'warning') {
            $warnings->push($event);
        }
    });

    $this->artisan('orders:settle-completed')->assertSuccessful();

    expect($order->fresh()->wallet_settled_at)->toBeNull();

    $settlementWarning = $warnings->first(fn (MessageLogged $event) => str_contains($event->message, 'Order settlement skipped'));
    expect($settlementWarning)->not->toBeNull();

    $this->artisan('orders:alert-unsettled')->assertSuccessful();

    $alertWarning = $warnings->first(fn (MessageLogged $event) => str_contains($event->message, 'Paid ended orders remain unsettled'));
    expect($alertWarning)->not->toBeNull()
        ->and($alertWarning->context['stuck_count'] ?? null)->toBe(1);

    Notification::assertSentTimes(StuckOrderSettlementsNotification::class, 1);
});

test('cancelling an order with insufficient pending_debit to fully reverse does NOT silently proceed to a terminal Cancelled status — it fails loudly or flags for review instead', function () {
    ['user' => $user, 'order' => $order] = paidInProgressOrder(500.0);

    $user->wallet->update(['pending_debit' => 100]);

    Sanctum::actingAs($user, ['user-api'], 'user-api');

    $this->postJson(action([UserOrderController::class, 'cancel'], ['order' => $order]), [
        'reason' => 'Provider did not start the work as agreed',
    ])->assertUnprocessable();

    expect($order->fresh()->status)->toBe(OrderStatusEnum::InProgress)
        ->and($order->fresh()->cancelled_at)->toBeNull()
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(100.0);

    expect(fn () => app(CancelOrderPaymentAction::class)->handle($order->fresh()))
        ->toThrow(OrdersException::class);
});

test('cancelling an order with fully sufficient holds still succeeds exactly as before — regression', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = paidInProgressOrder(500.0);

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $provider->wallet->fresh()->pending_credit)->toBe(500.0);

    app(CancelOrderPaymentAction::class)->handle($order);

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $provider->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $provider->wallet->fresh()->pending_debit)->toBe(0.0);
});
