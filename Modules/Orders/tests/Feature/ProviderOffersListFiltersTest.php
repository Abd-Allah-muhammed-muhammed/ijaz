<?php

use Illuminate\Http\Request;
use Modules\Orders\Database\Factories\OrderOfferFactory;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Http\Controllers\Provider\OrderController;
use Modules\Orders\Http\Resources\Dashboard\OfferResource;
use Modules\Orders\Models\Order;

beforeEach(function (): void {
    withoutOrdersLocaleMiddleware();
});

test('OfferResource now exposes the parent order\'s title (and client name if readily available) via a slim nested order object', function (): void {
    ['owner' => $owner, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        orderAttrs: ['title' => 'Kitchen Remodel Project'],
    );

    $offer->load(['order.user']);

    $payload = OfferResource::make($offer)->resolve(Request::create('/'));

    expect($payload)->toHaveKeys([
        'id',
        'price',
        'description',
        'status',
        'created_at',
        'order_id',
        'user_id',
        'provider_id',
        'category_id',
        'order',
    ])
        ->and($payload['order'])->toMatchArray([
            'id' => $order->id,
            'title' => 'Kitchen Remodel Project',
        ])
        ->and($payload['order']['user'])->toMatchArray([
            'id' => $owner->id,
            'name' => $owner->name,
        ])
        ->and($payload['order_id'])->toBe($order->id)
        ->and($payload['provider_id'])->toBe($provider->id);
});

test('existing OfferResource fields are unchanged — regression, this is additive', function (): void {
    ['order' => $order, 'offer' => $offer] = createOrderWithOffer([
        'title' => 'Additive Resource Regression',
    ], [
        'price' => 321.5,
        'description' => 'Keep me',
        'status' => OfferStatusEnum::Pending,
    ]);

    $offer->load(['order.user', 'provider']);
    $payload = OfferResource::make($offer)->resolve(Request::create('/'));

    expect($payload['id'])->toBe($offer->id)
        ->and((float) $payload['price'])->toBe(321.5)
        ->and($payload['description'])->toBe('Keep me')
        ->and($payload['status']['value'])->toBe(OfferStatusEnum::Pending->value)
        ->and($payload['order_id'])->toBe($order->id)
        ->and($payload['user_id'])->toBe($order->user_id)
        ->and($payload['category_id'])->toBe($order->category_id)
        ->and($payload)->toHaveKey('provider')
        ->and($payload)->toHaveKey('created_at')
        ->and($payload)->toHaveKey('order');
});

test('the provider offers list endpoint accepts a search param filtering by order title', function (): void {
    $provider = createWalletProvider();

    $matchingOrder = Order::factory()->create([
        'title' => 'UniqueOfferListSearchTitle',
        'status' => OrderStatusEnum::New,
    ]);
    $otherOrder = Order::factory()->create([
        'title' => 'UnrelatedOfferListTitle',
        'status' => OrderStatusEnum::New,
    ]);

    $matchingOffer = OrderOfferFactory::new()
        ->forOrder($matchingOrder)
        ->forProvider($provider)
        ->create(['status' => OfferStatusEnum::Pending]);

    OrderOfferFactory::new()
        ->forOrder($otherOrder)
        ->forProvider($provider)
        ->create(['status' => OfferStatusEnum::Pending]);

    $this->actingAs($provider, 'provider')
        ->get(action([OrderController::class, 'offers'], ['search' => 'UniqueOfferListSearchTitle']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Orders/Offers')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $matchingOffer->id)
            ->where('rows.data.0.order.title', 'UniqueOfferListSearchTitle')
        );
});

test('the provider offers list endpoint accepts a status param filtering by offer status', function (): void {
    $provider = createWalletProvider();
    $order = Order::factory()->create(['status' => OrderStatusEnum::New]);

    $pending = OrderOfferFactory::new()
        ->forOrder($order)
        ->forProvider($provider)
        ->create(['status' => OfferStatusEnum::Pending]);

    OrderOfferFactory::new()
        ->forOrder($order)
        ->forProvider($provider)
        ->create(['status' => OfferStatusEnum::Accepted]);

    $this->actingAs($provider, 'provider')
        ->get(action([OrderController::class, 'offers'], ['status' => OfferStatusEnum::Pending->value]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Orders/Offers')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $pending->id)
            ->where('rows.data.0.status.value', OfferStatusEnum::Pending->value)
        );
});

test('omitting search/status still returns all the provider\'s offers, unfiltered — regression', function (): void {
    $provider = createWalletProvider();
    $order = Order::factory()->create(['status' => OrderStatusEnum::New]);

    OrderOfferFactory::new()
        ->forOrder($order)
        ->forProvider($provider)
        ->create(['status' => OfferStatusEnum::Pending]);

    OrderOfferFactory::new()
        ->forOrder($order)
        ->forProvider($provider)
        ->create(['status' => OfferStatusEnum::Accepted]);

    $otherProvider = createWalletProvider();
    OrderOfferFactory::new()
        ->forOrder($order)
        ->forProvider($otherProvider)
        ->create(['status' => OfferStatusEnum::Pending]);

    $this->actingAs($provider, 'provider')
        ->get(action([OrderController::class, 'offers']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Orders/Offers')
            ->has('rows.data', 2)
        );
});
