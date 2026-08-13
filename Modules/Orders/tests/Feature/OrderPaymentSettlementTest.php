<?php

use App\Models\Provider;
use App\Models\User;
use Modules\Orders\Actions\SettleOrderPaymentAction;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;

/**
 * @return array{user: User, provider: Provider, order: Order, offer: OrderOffer}
 */
function paidEndedOrder(float $price = 500.0, OrderStatusEnum $endedStatus = OrderStatusEnum::EndedByClient): array
{
    $context = createOrderPaymentContext($price);

    $payment = createPaymentFor($context['user'], $context['offer'], [
        'amount' => $price,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment));

    $order = $context['order']->fresh();
    $order->update(['status' => $endedStatus]);

    return [
        ...$context,
        'order' => $order->fresh(),
    ];
}

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

    expect($fees)->toBe(50.0)
        ->and($net)->toBe(450.0);

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

    expect($fees)->toBe(50.0)
        ->and((float) $providerWallet->pending_debit)->toBe(-50.0)
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
