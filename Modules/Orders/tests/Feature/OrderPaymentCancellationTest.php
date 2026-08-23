<?php

use Modules\Orders\Actions\CancelOrderPaymentAction;
use Modules\Orders\Actions\SettleOrderPaymentAction;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;

test('cancelling a paid order reverses the user pending_debit hold in full', function () {
    ['user' => $user, 'order' => $order] = paidInProgressOrder(500.0);

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $user->wallet->fresh()->balance)->toBe(0.0);

    app(CancelOrderPaymentAction::class)->handle($order);

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $user->wallet->fresh()->balance)->toBe(0.0);
});

test('cancelling a paid order reverses the provider pending_credit AND the negative pending_debit fee hold, both back to zero', function () {
    ['provider' => $provider, 'order' => $order] = paidInProgressOrder(500.0);

    $fees = (float) $order->fresh()->provider_fees;
    $providerWallet = $provider->wallet->fresh();

    expect($fees)->toBeGreaterThan(0.0)
        ->and((float) $providerWallet->pending_credit)->toBe(500.0)
        ->and((float) $providerWallet->pending_debit)->toBe(-$fees)
        ->and((float) $providerWallet->balance)->toBe(0.0);

    app(CancelOrderPaymentAction::class)->handle($order);

    $providerWallet = $provider->wallet->fresh();

    expect((float) $providerWallet->pending_credit)->toBe(0.0)
        ->and((float) $providerWallet->pending_debit)->toBe(0.0)
        ->and((float) $providerWallet->balance)->toBe(0.0);
});

test('cancelling an order that was never paid (no pending holds exist) does not error and is a safe no-op', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderPaymentContext(500.0);

    expect($order->status)->toBe(OrderStatusEnum::OfferProvided)
        ->and($order->wallet_settled_at)->toBeNull();

    app(CancelOrderPaymentAction::class)->handle($order);

    expect($order->fresh()->wallet_settled_at)->toBeNull()
        ->and((float) ($user->wallet?->fresh()?->pending_debit ?? 0))->toBe(0.0)
        ->and((float) ($provider->wallet?->fresh()?->pending_credit ?? 0))->toBe(0.0)
        ->and((float) ($provider->wallet?->fresh()?->pending_debit ?? 0))->toBe(0.0)
        ->and((float) ($provider->wallet?->fresh()?->balance ?? 0))->toBe(0.0);
});

test('an order already settled (wallet_settled_at is set) cannot be cancelled-and-reversed — guard against double-processing on whichever order status transitions still permit reaching a cancelled state after settlement, if any', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = paidInProgressOrder(500.0);

    $net = (float) $order->price - (float) $order->provider_fees;

    app(SettleOrderPaymentAction::class)->handle($order);

    expect($order->fresh()->wallet_settled_at)->not->toBeNull()
        ->and((float) $provider->wallet->fresh()->balance)->toBe($net)
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(0.0);

    app(CancelOrderPaymentAction::class)->handle($order->fresh());
})->throws(OrdersException::class);
