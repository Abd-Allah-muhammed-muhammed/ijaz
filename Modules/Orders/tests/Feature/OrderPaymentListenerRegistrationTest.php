<?php

use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Wallet\Models\WalletTransaction;

/**
 * Proves Orders self-registration: dispatching PaymentCompleted (not calling the
 * listener directly) runs HandleOrderPaymentCompleted and applies order + wallet side effects.
 */
it('handles PaymentCompleted end-to-end via Orders self-registered listeners', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderPaymentContext(500.0);

    $payment = createPaymentFor($user, $offer, [
        'amount' => 500,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment));

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Paid)
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::InProgress)
        ->and((float) $order->fresh()->price)->toBe(500.0);

    $fees = (float) $order->fresh()->provider_fees;

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $provider->wallet->fresh()->pending_credit)->toBe(500.0)
        ->and((float) $provider->wallet->fresh()->pending_debit)->toBe(-$fees);

    expect(
        WalletTransaction::query()
            ->where('operation_type', $offer::class)
            ->where('operation_id', $offer->id)
            ->exists()
    )->toBeTrue();
});
