<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Modules\Orders\Actions\SettleOrderPaymentAction;

test('a completed order settles automatically after the dispute window elapses: user pending_debit clears, provider pending_credit converts to balance net of provider_fees', function () {
    setWalletSetting('order_dispute_window_hours', '48');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = paidEndedOrder(500.0);

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $provider->wallet->fresh()->pending_credit)->toBe(500.0)
        ->and((float) $provider->wallet->fresh()->balance)->toBe(0.0)
        ->and($order->wallet_settled_at)->toBeNull();

    $this->travel(49)->hours();

    $this->artisan('orders:settle-completed')->assertSuccessful();

    $gross = (float) $order->fresh()->price;
    $fees = (float) $order->fresh()->provider_fees;
    $net = $gross - $fees;

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $user->wallet->fresh()->balance)->toBe(0.0)
        ->and((float) $provider->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $provider->wallet->fresh()->balance)->toBe($net)
        ->and($order->fresh()->wallet_settled_at)->not->toBeNull();
});

test('an order still within the dispute window is NOT settled by the scheduled command', function () {
    setWalletSetting('order_dispute_window_hours', '48');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = paidEndedOrder(500.0);

    $this->artisan('orders:settle-completed')->assertSuccessful();

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $provider->wallet->fresh()->pending_credit)->toBe(500.0)
        ->and((float) $provider->wallet->fresh()->balance)->toBe(0.0)
        ->and($order->fresh()->wallet_settled_at)->toBeNull();
});

test('an order settled once is never settled twice even if the command runs again', function () {
    setWalletSetting('order_dispute_window_hours', '48');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = paidEndedOrder(500.0);

    $this->travel(49)->hours();

    $this->artisan('orders:settle-completed')->assertSuccessful();

    $settledAt = $order->fresh()->wallet_settled_at;
    $net = (float) $order->price - (float) $order->provider_fees;

    expect($settledAt)->not->toBeNull()
        ->and((float) $provider->wallet->fresh()->balance)->toBe($net);

    $this->artisan('orders:settle-completed')->assertSuccessful();

    expect($order->fresh()->wallet_settled_at?->eq($settledAt))->toBeTrue()
        ->and((float) $provider->wallet->fresh()->balance)->toBe($net)
        ->and((float) $provider->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(0.0);
});

test('SettleOrderPaymentAction correctly computes net = gross - provider_fees, matching the fee-withholding pattern used by Guarantor release', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = paidEndedOrder(500.0);

    $gross = (float) $order->price;
    $fees = (float) $order->provider_fees;
    $net = $gross - $fees;

    expect($fees)->toBeGreaterThan(0.0)
        ->and($net)->toBe($gross - $fees);

    app(SettleOrderPaymentAction::class)->handle($order);

    expect((float) $provider->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $provider->wallet->fresh()->balance)->toBe($net)
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $user->wallet->fresh()->balance)->toBe(0.0)
        ->and($order->fresh()->wallet_settled_at)->not->toBeNull();

    app(SettleOrderPaymentAction::class)->handle($order->fresh());

    expect((float) $provider->wallet->fresh()->balance)->toBe($net)
        ->and((float) $provider->wallet->fresh()->pending_credit)->toBe(0.0);
});

test('after order settlement, the provider pending_debit fee hold is fully cleared to zero — not left at a negative residual value that would inflate available balance', function () {
    ['provider' => $provider, 'order' => $order] = paidEndedOrder(500.0);

    $fees = (float) $order->provider_fees;
    $net = (float) $order->price - $fees;
    $providerWallet = $provider->wallet->fresh();

    expect($fees)->toBeGreaterThan(0.0)
        ->and((float) $providerWallet->pending_debit)->toBe(-$fees);

    app(SettleOrderPaymentAction::class)->handle($order);

    $providerWallet = $provider->wallet->fresh();

    expect((float) $providerWallet->pending_debit)->toBe(0.0)
        ->and((float) $providerWallet->balance)->toBe($net)
        ->and((float) ($providerWallet->balance - $providerWallet->pending_debit))->toBe($net);

    app(SettleOrderPaymentAction::class)->handle($order->fresh());

    expect((float) $provider->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $provider->wallet->fresh()->balance)->toBe($net);
});

test('settling an order never drives provider pending_credit or user pending_debit negative — if the wallet does not have enough pending to cover this order price, settlement fails safely and logs/skips rather than going negative', function () {
    $warnings = collect();
    Log::listen(function (MessageLogged $event) use ($warnings) {
        if ($event->level === 'warning') {
            $warnings->push($event);
        }
    });

    ['user' => $user, 'provider' => $provider, 'order' => $order] = paidEndedOrder(500.0);

    $user->wallet->update(['pending_debit' => 100]);
    $provider->wallet->update(['pending_credit' => 100]);

    app(SettleOrderPaymentAction::class)->handle($order);

    $userWallet = $user->wallet->fresh();
    $providerWallet = $provider->wallet->fresh();

    expect((float) $userWallet->pending_debit)->toBe(100.0)
        ->and((float) $userWallet->pending_debit)->toBeGreaterThanOrEqual(0.0)
        ->and((float) $providerWallet->pending_credit)->toBe(100.0)
        ->and((float) $providerWallet->pending_credit)->toBeGreaterThanOrEqual(0.0)
        ->and((float) $providerWallet->balance)->toBe(0.0)
        ->and($order->fresh()->wallet_settled_at)->toBeNull();

    $warning = $warnings->first();

    expect($warning)->not->toBeNull()
        ->and($warning->message)->toContain('Order settlement skipped')
        ->and($warning->context['order_id'] ?? null)->toBe($order->id)
        ->and($warning->context)->toHaveKeys(['wallet_id', 'shortfall'])
        ->and((float) $warning->context['shortfall'])->toBeGreaterThan(0);
});

test('two overlapping runs of orders:settle-completed do not double-process the same batch', function () {
    setWalletSetting('order_dispute_window_hours', '48');

    ['provider' => $providerA, 'order' => $orderA] = paidEndedOrder(500.0);
    ['provider' => $providerB, 'order' => $orderB] = paidEndedOrder(300.0);

    $this->travel(49)->hours();

    $this->artisan('orders:settle-completed')->assertSuccessful();
    $this->artisan('orders:settle-completed')->assertSuccessful();

    $netA = (float) $orderA->price - (float) $orderA->provider_fees;
    $netB = (float) $orderB->price - (float) $orderB->provider_fees;

    expect($orderA->fresh()->wallet_settled_at)->not->toBeNull()
        ->and($orderB->fresh()->wallet_settled_at)->not->toBeNull()
        ->and((float) $providerA->wallet->fresh()->balance)->toBe($netA)
        ->and((float) $providerB->wallet->fresh()->balance)->toBe($netB)
        ->and((float) $providerA->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $providerB->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $providerA->wallet->fresh()->pending_credit)->toBeGreaterThanOrEqual(0.0)
        ->and((float) $providerB->wallet->fresh()->pending_credit)->toBeGreaterThanOrEqual(0.0);

    $event = collect(app(Schedule::class)->events())
        ->first(fn ($scheduled) => str_contains((string) $scheduled->command, 'orders:settle-completed'));

    expect($event)->not->toBeNull()
        ->and($event->withoutOverlapping)->toBeTrue();
});
