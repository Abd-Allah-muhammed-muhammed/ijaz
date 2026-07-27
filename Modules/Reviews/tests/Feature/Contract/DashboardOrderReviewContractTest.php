<?php

use App\Models\Provider;
use App\Models\User;
use Modules\Orders\Http\Resources\Dashboard\OrderResource;
use Modules\Orders\Models\Order;
use Modules\Reviews\Models\Review;

/**
 * Contract freeze for Dashboard Order reviews tab data
 * (consumed by resources/js/pages/Dashboard/Orders/components/reviews-tap.tsx).
 */
it('exposes dashboard order reviews with short reviewer_type and rating/comment', function () {
    $user = User::factory()->create(['f_name' => 'Sara', 'l_name' => 'User']);
    $provider = createWalletProvider(['name' => 'Pro Co']);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'provider_id' => $provider->id,
    ]);

    Review::query()->create([
        'reviewer_type' => User::class,
        'reviewer_id' => $user->id,
        'reviewee_type' => Provider::class,
        'reviewee_id' => $provider->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'rating' => 5,
        'comment' => 'Excellent',
    ]);

    Review::query()->create([
        'reviewer_type' => Provider::class,
        'reviewer_id' => $provider->id,
        'reviewee_type' => User::class,
        'reviewee_id' => $user->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'rating' => 4,
        'comment' => 'Good client',
    ]);

    $order->load(['reviews.reviewer', 'reviews.reviewee']);

    $payload = OrderResource::make($order)->response()->getData(true);

    expect($payload['reviews'])->toHaveCount(2);

    $types = collect($payload['reviews'])->pluck('reviewer_type')->all();

    expect($types)->toContain('User')
        ->and($types)->toContain('Provider');

    foreach ($payload['reviews'] as $review) {
        expect($review)->toHaveKeys([
            'id',
            'rating',
            'comment',
            'reviewer_type',
            'reviewer',
            'reviewee_type',
            'reviewee',
        ])
            ->and($review['reviewer'])->toHaveKeys(['name', 'image', 'socket_id'])
            ->and($review['reviewee'])->toHaveKeys(['name', 'image', 'socket_id']);
    }
});
