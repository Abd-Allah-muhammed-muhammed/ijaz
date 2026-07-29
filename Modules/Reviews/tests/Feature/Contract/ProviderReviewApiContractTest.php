<?php

use App\Http\Resources\Api\V1\ProviderResource;
use App\Models\Provider;
use App\Models\User;
use Modules\Orders\Models\Order;
use Modules\Reviews\Models\Review;

/**
 * Contract freeze for nested Provider API reviews.
 *
 * Bug fix (Step 1): Api\V1\ProviderResource previously called
 * whenAggregated('reviews', 'avg', 'rate', $this->reviews_avg_rate), which looks for
 * attribute `reviews_rate_avg` / property `reviews_avg_rate`. Repository/API load uses
 * loadAvg('reviews', 'rating') → `reviews_avg_rating`. Args are (relation, column, aggregate).
 * Fixed to whenAggregated('reviews', 'rating', 'avg') so `rate` is populated correctly.
 */
it('exposes provider rate from reviews_avg_rating and nested review shape', function () {
    $user = User::factory()->create(['f_name' => 'Ali', 'l_name' => 'Client']);
    $provider = createWalletProvider(['name' => 'Rated Co']);
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
        'rating' => 4,
        'comment' => 'Solid work',
    ]);

    $provider->load(['reviews.reviewer']);
    $provider->loadAvg('reviews', 'rating');

    $payload = json_decode(json_encode(ProviderResource::make($provider)->resolve()), true);

    expect($payload)->toHaveKeys(['rate', 'reviews'])
        ->and((float) $payload['rate'])->toBe(4.0)
        ->and($payload['reviews'])->toHaveCount(1);

    $review = $payload['reviews'][0];

    expect($review)->toHaveKeys([
        'id',
        'reviewer',
        'operation_id',
        'operation_type',
        'rating',
        'comment',
        'created_at',
    ])
        ->and($review['operation_type'])->toBe('Order')
        ->and($review['operation_id'])->toBe($order->id)
        ->and($review['rating'])->toBe(4)
        ->and($review['comment'])->toBe('Solid work')
        ->and($review['reviewer'])->toHaveKeys(['id', 'name', 'image', 'socket_id'])
        ->and($review['reviewer']['id'])->toBe($user->id);
});
