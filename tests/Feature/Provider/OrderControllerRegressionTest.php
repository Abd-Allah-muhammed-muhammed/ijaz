<?php

use App\Enums\CategoryFeesTypeEnum;
use App\Models\Provider;
use App\Models\Review;
use App\Models\User;
use App\Notifications\User\OrderAcceptedOfferUpdatedNotification;
use App\Notifications\User\OrderOfferCreatedNotification;
use Illuminate\Support\Facades\Notification;
use Modules\Marketplace\Models\Category;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Http\Controllers\Provider\OrderController;
use Modules\Orders\Models\Order;

beforeEach(function () {
    Notification::fake();
    withoutOrdersLocaleMiddleware();
    setWalletSetting('testing_fees', '20');
});

it('lists provider assigned orders on index', function () {
    $provider = createWalletProvider();
    $other = createWalletProvider();
    Order::factory()->create(['provider_id' => $provider->id, 'status' => OrderStatusEnum::InProgress]);
    Order::factory()->create(['provider_id' => $other->id, 'status' => OrderStatusEnum::InProgress]);

    $this->actingAs($provider, 'provider')
        ->get(action([OrderController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Orders/Index')
            ->has('rows.data', 1)
        );
});

it('lists recommended new orders matching provider categories', function () {
    $provider = createWalletProvider();
    $category = Category::factory()->create();
    $provider->categories()->attach($category->id);

    $matching = Order::factory()->create([
        'category_id' => $category->id,
        'status' => OrderStatusEnum::New,
        'accepted_offer_id' => null,
        'provider_id' => null,
    ]);
    Order::factory()->create([
        'status' => OrderStatusEnum::New,
        'accepted_offer_id' => null,
        'provider_id' => null,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action([OrderController::class, 'new']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Orders/Recommended')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $matching->id)
        );
});

it('shows an order with provider-scoped offers', function () {
    ['provider' => $provider, 'order' => $order] = createOrderWithOffer();

    $this->actingAs($provider, 'provider')
        ->get(action([OrderController::class, 'show'], ['order' => $order]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Orders/Show')
            ->where('order.id', $order->id)
        );
});

it('submits an offer and notifies the order owner', function () {
    $provider = createWalletProvider();
    $category = Category::factory()->create();
    $provider->categories()->attach($category->id);
    $owner = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'category_id' => $category->id,
        'status' => OrderStatusEnum::New,
        'provider_id' => null,
        'accepted_offer_id' => null,
    ]);

    $this->actingAs($provider, 'provider')
        ->post(action([OrderController::class, 'submitOffer'], ['order' => $order]), [
            'price' => 175,
            'description' => 'I can do this',
        ])
        ->assertRedirect(route('provider.orders.show', $order));

    expect($order->offers()->where('provider_id', $provider->id)->exists())->toBeTrue();
    Notification::assertSentTo($owner, OrderOfferCreatedNotification::class);
});

/**
 * KNOWN: Provider updateOffer uses config('payment.default').'_fees' while User
 * updateOfferStatus uses PaymentService::getDefaultDriver().'_fees'. Today both
 * resolve to the same key when getDefaultDriver() === config('payment.default').
 */
it('updates an accepted offer price and recalculates provider_fees via config payment.default', function () {
    ['owner' => $owner, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        orderAttrs: [
            'status' => OrderStatusEnum::OfferProvided,
            'price' => 200,
            'user_fees' => 0,
            'provider_fees' => 31.5,
        ],
        offerAttrs: [
            'status' => OfferStatusEnum::Accepted,
            'price' => 200,
        ],
        categoryAttrs: ['fees' => 10.0, 'fees_type' => CategoryFeesTypeEnum::FIXED],
    );
    $order->update([
        'accepted_offer_id' => $offer->id,
        'provider_id' => $provider->id,
    ]);

    $this->actingAs($provider, 'provider')
        ->post(action([OrderController::class, 'updateOffer'], [
            'order' => $order,
            'offer' => $offer,
        ]), [
            'price' => 300,
            'description' => 'Updated price',
        ])
        ->assertRedirect(route('provider.orders.show', $order));

    // categoryFees=10 FIXED, gateway=20 → 20 + 10 + 1.5 = 31.5 (unchanged category fee base)
    expect((float) $order->fresh()->price)->toBe(300.0)
        ->and((float) $order->fresh()->provider_fees)->toBe(31.5);

    Notification::assertSentTo($owner, OrderAcceptedOfferUpdatedNotification::class);
});

it('lists provider order offers', function () {
    ['provider' => $provider] = createOrderWithOffer();

    $this->actingAs($provider, 'provider')
        ->get(action([OrderController::class, 'offers']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Orders/Offers')
            ->has('rows.data', 1)
        );
});

/**
 * Pending offers may be deleted; processed (non-pending) offers may not.
 * (Previously inverted: Pending was blocked with the "processed" message.)
 */
it('allows deleting a pending offer', function () {
    ['provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        offerAttrs: ['status' => OfferStatusEnum::Pending],
    );

    $this->actingAs($provider, 'provider');
    auth()->shouldUse('provider');

    $this->delete(action([OrderController::class, 'deleteOffer'], [
        'order' => $order,
        'offer' => $offer,
    ]))->assertRedirect(route('provider.orders.show', $order));

    expect($order->offers()->whereKey($offer->id)->exists())->toBeFalse();
});

it('blocks deleting an accepted offer with the processed error message', function () {
    ['provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        offerAttrs: ['status' => OfferStatusEnum::Accepted],
    );

    $this->actingAs($provider, 'provider');
    auth()->shouldUse('provider');

    $response = $this->from(route('provider.orders.show', $order))
        ->delete(action([OrderController::class, 'deleteOffer'], [
            'order' => $order,
            'offer' => $offer,
        ]));

    $response->assertRedirect();
    expect($offer->fresh())->not->toBeNull();
    $response->assertSessionHas('error', __('you can not delete this offer because it has been processed.'));
});

it('blocks deleting a rejected offer with the processed error message', function () {
    ['provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        offerAttrs: ['status' => OfferStatusEnum::Rejected],
    );

    $this->actingAs($provider, 'provider');
    auth()->shouldUse('provider');

    $response = $this->from(route('provider.orders.show', $order))
        ->delete(action([OrderController::class, 'deleteOffer'], [
            'order' => $order,
            'offer' => $offer,
        ]));

    $response->assertRedirect();
    expect($offer->fresh())->not->toBeNull();
    $response->assertSessionHas('error', __('you can not delete this offer because it has been processed.'));
});

it('ends an in-progress order as the assigned provider', function () {
    $provider = createWalletProvider();
    $order = Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::InProgress,
    ]);

    $this->actingAs($provider, 'provider');
    auth()->shouldUse('provider');

    $this->from(route('provider.orders.show', $order))
        ->post(action([OrderController::class, 'end'], ['order' => $order]))
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatusEnum::EndedByProvider);
});

/**
 * KNOWN ISSUE (lock-in): updateReview stores reviewer_id = $order->user_id while
 * reviewer_type = Provider::class, and reviewee_id = auth()->user()->id (provider)
 * with reviewee_type = User::class. IDs/types are crossed vs the User endAndReview path.
 */
it('stores review with crossed reviewer_id and reviewee_id as currently implemented', function () {
    $provider = createWalletProvider();
    $owner = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::EndedByClient,
    ]);

    $this->actingAs($provider, 'provider');
    auth()->shouldUse('provider');

    $this->from(route('provider.orders.show', $order))
        ->post(action([OrderController::class, 'updateReview'], ['order' => $order]), [
            'rating' => 4,
            'comment' => 'Good client',
        ])
        ->assertRedirect();

    $review = Review::query()->where([
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'reviewer_type' => Provider::class,
    ])->first();

    expect($review)->not->toBeNull()
        // Lock-in of the suspicious mismatch:
        ->and($review->reviewer_id)->toBe($owner->id)
        ->and($review->reviewee_type)->toBe(User::class)
        ->and($review->reviewee_id)->toBe($provider->id)
        ->and($review->rating)->toBe(4);
});
