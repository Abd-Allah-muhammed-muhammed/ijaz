<?php

/**
 * Contract freezes: mobile/API responses must stay byte-identical in shape after
 * lazy-loading eager-load fixes (no new/removed keys from the fix itself).
 */

use App\Models\Provider;
use App\Models\User;
use App\Support\LazyLoading\LazyLoadingSweepFixture;
use Laravel\Sanctum\Sanctum;
use Modules\Orders\Models\Order;
use Modules\Reviews\Models\Review;

it('providers/get review payload includes reviewer and reviewee when both are eager-loaded', function (): void {
    $user = User::factory()->create(['f_name' => 'Contract', 'l_name' => 'User']);
    $provider = createWalletProvider(['name' => 'Contract Provider']);
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
        'comment' => 'Contract freeze',
    ]);

    Sanctum::actingAs($user, ['*'], 'user-api');

    $a = $this->getJson('/api/v1/user/providers/get?provider_id='.$provider->id)->assertSuccessful()->json('data');
    $b = $this->getJson('/api/v1/user/providers/get?provider_id='.$provider->id)->assertSuccessful()->json('data');

    expect($a)->toEqual($b)
        ->and($a['reviews'][0])->toHaveKeys(['id', 'reviewer', 'reviewee', 'rating', 'comment', 'operation_id', 'operation_type', 'created_at'])
        ->and($a['reviews'][0]['reviewer'])->toHaveKeys(['id', 'name', 'image', 'socket_id'])
        ->and($a['reviews'][0]['reviewee'])->toHaveKeys(['id', 'name', 'image', 'socket_id']);
});

it('classifieds electronics/all nested translation fields are stable across identical requests', function (): void {
    $fixture = LazyLoadingSweepFixture::seed();

    $a = $this->getJson('/api/v1/classifieds/electronics/all')->assertSuccessful()->json();
    $b = $this->getJson('/api/v1/classifieds/electronics/all')->assertSuccessful()->json();

    // Drop volatile timestamps if present at top-level wrappers only — item bodies must match.
    expect($a)->toEqual($b);

    $item = $a['data']['items'][0] ?? $a['data'][0] ?? null;
    expect($item)->not->toBeNull()
        ->and($item)->toHaveKeys(['id', 'title', 'device_category', 'electronic_brand', 'city', 'region']);
});
