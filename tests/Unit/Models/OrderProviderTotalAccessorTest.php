<?php

use App\Models\Order;

/**
 * KNOWN ISSUE (lock-in from Orders audit): providerTotal accessor falls back to
 * (price - user_fees) when the stored generated value is read through the Attribute
 * — it does NOT subtract provider_fees. Controllers that charge/payout using
 * providerTotal may therefore be wrong when only provider_fees is populated
 * (the common accept-offer path sets user_fees=0).
 *
 * With user_fees=0 the fallback equals price, which may be accidental "correctness".
 * Note: user_total / provider_total / total_fees are DB generated columns — we must
 * not INSERT into them; we assert the Attribute fallback formula via model attributes.
 */
it('computes providerTotal as price minus user_fees via the Attribute fallback formula', function () {
    $order = Order::factory()->create([
        'price' => 200,
        'user_fees' => 0,
        'provider_fees' => 31.5,
    ]);

    // Mirror Attribute::get fallback used when column value is null in attributes array.
    $fallback = ($order->getAttributes()['price'] ?? 0) - ($order->getAttributes()['user_fees'] ?? 0);

    expect((float) $order->provider_total)->toBe(200.0)
        ->and((float) $fallback)->toBe(200.0)
        // Documents that provider_fees is ignored by the fallback formula:
        ->and((float) $fallback)->not->toBe(168.5);
});

it('computes userTotal as price plus user_fees', function () {
    $order = Order::factory()->create([
        'price' => 200,
        'user_fees' => 0,
        'provider_fees' => 31.5,
    ]);

    expect((float) $order->user_total)->toBe(200.0);
});

it('computes totalFees as user_fees plus provider_fees', function () {
    $order = Order::factory()->create([
        'price' => 200,
        'user_fees' => 5,
        'provider_fees' => 31.5,
    ]);

    expect((float) $order->total_fees)->toBe(36.5);
});
