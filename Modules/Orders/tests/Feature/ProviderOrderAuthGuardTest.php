<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Http\Controllers\Provider\OrderController;
use Modules\Orders\Models\Order;
use Modules\Reviews\Models\Review;

beforeEach(function () {
    Notification::fake();
    withoutOrdersLocaleMiddleware();
});

/**
 * actingAs($provider, 'provider') also calls shouldUse('provider'), which masks the
 * old auth()->user() bug. Reset default guard to 'web' so auth()->user() is empty
 * while auth('provider')->user() remains the provider — matching production where
 * auth:provider middleware authenticates the provider guard without flipping default.
 */
function actingAsProviderWithoutDefaultGuard($provider): void
{
    test()->actingAs($provider, 'provider');
    auth()->shouldUse('web');
}

it('deletes a pending offer when only the provider guard is authenticated', function () {
    ['provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        offerAttrs: ['status' => OfferStatusEnum::Pending],
    );

    actingAsProviderWithoutDefaultGuard($provider);

    expect(auth()->user())->toBeNull()
        ->and(auth('provider')->user()->is($provider))->toBeTrue();

    $this->delete(action([OrderController::class, 'deleteOffer'], [
        'order' => $order,
        'offer' => $offer,
    ]))->assertRedirect(route('provider.orders.show', $order));

    expect($order->offers()->whereKey($offer->id)->exists())->toBeFalse();
});

it('ends an in-progress order when only the provider guard is authenticated', function () {
    $provider = createWalletProvider();
    $order = Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::InProgress,
    ]);

    actingAsProviderWithoutDefaultGuard($provider);

    expect(auth()->user())->toBeNull();

    $this->post(action([OrderController::class, 'end'], ['order' => $order]))
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatusEnum::EndedByProvider);
});

it('updates a review when only the provider guard is authenticated', function () {
    $owner = User::factory()->create();
    $provider = createWalletProvider();
    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::EndedByClient,
    ]);

    actingAsProviderWithoutDefaultGuard($provider);

    expect(auth()->user())->toBeNull();

    $this->from(route('provider.orders.show', $order))
        ->post(action([OrderController::class, 'updateReview'], ['order' => $order]), [
            'rating' => 5,
            'comment' => 'Excellent',
        ])
        ->assertRedirect();

    $review = Review::query()->where([
        'operation_type' => Order::class,
        'operation_id' => $order->id,
    ])->first();

    expect($review)->not->toBeNull()
        ->and($review->reviewer_id)->toBe($provider->id)
        ->and($review->reviewee_id)->toBe($owner->id);
});
