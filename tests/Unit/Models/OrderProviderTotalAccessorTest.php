<?php

use Modules\Orders\Models\Order;

/**
 * providerTotal must match the DB generated column formula: price - provider_fees.
 * (Previously the Attribute fallback incorrectly used price - user_fees.)
 */
it('computes providerTotal as price minus provider_fees', function () {
    $order = Order::factory()->create([
        'price' => 200,
        'user_fees' => 0,
        'provider_fees' => 31.5,
    ]);

    expect((float) $order->provider_total)->toBe(168.5);
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
