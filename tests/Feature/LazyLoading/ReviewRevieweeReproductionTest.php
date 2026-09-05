<?php

/**
 * Regression: Ahmed Diab's GET /api/v1/user/providers/get must not 500 on reviewee
 * under Model::preventLazyLoading (staging).
 */

use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\Sanctum;
use Modules\Orders\Models\Order;
use Modules\Reviews\Http\Resources\Api\V1\ReviewResource;
use Modules\Reviews\Models\Review;

beforeEach(function (): void {
    expect(Model::preventsLazyLoading())->toBeTrue();
});

it('does not lazy-load reviewee from ReviewResource when the relation is unloaded', function (): void {
    $user = User::factory()->create();
    $provider = createWalletProvider(['name' => 'Reviewee Probe Co']);
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
        'comment' => 'Probe',
    ]);

    $review = Review::query()->with('reviewer')->where('operation_id', $order->id)->firstOrFail();
    $review->preventsLazyLoading = true;

    expect($review->relationLoaded('reviewee'))->toBeFalse();

    $payload = ReviewResource::make($review)->resolve();

    expect($payload)->toHaveKey('reviewer')
        ->and($payload)->not->toHaveKey('reviewee');
});

it('returns 200 for GET /api/v1/user/providers/get when the provider has multiple reviews', function (): void {
    $user = User::factory()->create();
    $provider = createWalletProvider(['name' => 'Ahmed Path Provider', 'phone' => '0501112233']);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'provider_id' => $provider->id,
    ]);

    foreach ([4, 5] as $rating) {
        Review::query()->create([
            'reviewer_type' => User::class,
            'reviewer_id' => $user->id,
            'reviewee_type' => Provider::class,
            'reviewee_id' => $provider->id,
            'operation_type' => Order::class,
            'operation_id' => $order->id,
            'rating' => $rating,
            'comment' => 'Review '.$rating,
        ]);
    }

    Sanctum::actingAs($user, ['*'], 'user-api');

    $response = $this->getJson('/api/v1/user/providers/get?provider_id='.$provider->id);

    $response->assertSuccessful();
    expect($response->json('data.reviews'))->toBeArray()->toHaveCount(2)
        ->and($response->json('data.reviews.0'))->toHaveKeys(['reviewer', 'reviewee']);
});
