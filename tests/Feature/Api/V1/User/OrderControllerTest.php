<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Http\Controllers\Api\V1\OrderController;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;

beforeEach(function () {
    Notification::fake();
});

/**
 * @return array{owner: User, order: Order, offer: OrderOffer}
 */
function createOwnedOrderWithPendingOffer(?User $owner = null): array
{
    $owner ??= User::factory()->create();
    $provider = createWalletProvider();
    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'status' => OrderStatusEnum::New,
        'provider_id' => null,
        'accepted_offer_id' => null,
    ]);

    $offer = OrderOffer::query()->create([
        'order_id' => $order->id,
        'user_id' => $owner->id,
        'provider_id' => $provider->id,
        'category_id' => $order->category_id,
        'price' => 250,
        'description' => 'Pending offer for ownership tests',
        'status' => OfferStatusEnum::Pending,
    ]);

    return compact('owner', 'order', 'offer');
}

test('user cannot update offer status on an order they do not own', function () {
    ['order' => $order, 'offer' => $offer] = createOwnedOrderWithPendingOffer();
    $attacker = User::factory()->create();

    Sanctum::actingAs($attacker, ['user-api'], 'user-api');

    $this->postJson(
        action([OrderController::class, 'updateOfferStatus'], [
            'order' => $order,
            'offer' => $offer,
        ]),
        ['status' => OfferStatusEnum::Rejected->value],
    )->assertNotFound();

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Pending)
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::New)
        ->and($order->fresh()->provider_id)->toBeNull()
        ->and($order->fresh()->accepted_offer_id)->toBeNull();
});

test('user can update offer status on their own order', function () {
    ['owner' => $owner, 'order' => $order, 'offer' => $offer] = createOwnedOrderWithPendingOffer();

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->postJson(
        action([OrderController::class, 'updateOfferStatus'], [
            'order' => $order,
            'offer' => $offer,
        ]),
        ['status' => OfferStatusEnum::Rejected->value],
    )->assertOk()
        ->assertJsonPath('message', __('data saved successfully'));

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Rejected)
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::New);
});

test('user cannot view another users order', function () {
    ['order' => $order] = createOwnedOrderWithPendingOffer();
    $attacker = User::factory()->create();

    Sanctum::actingAs($attacker, ['user-api'], 'user-api');

    $this->getJson(action([OrderController::class, 'show'], ['order' => $order]))
        ->assertNotFound();
});

test('user can still view their own order', function () {
    ['owner' => $owner, 'order' => $order] = createOwnedOrderWithPendingOffer();

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->getJson(action([OrderController::class, 'show'], ['order' => $order]))
        ->assertOk()
        ->assertJsonPath('data.id', $order->id);
});

test('user cannot delete another users order', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'status' => OrderStatusEnum::New,
        'provider_id' => null,
        'accepted_offer_id' => null,
    ]);

    Sanctum::actingAs($attacker, ['user-api'], 'user-api');

    $this->deleteJson(action([OrderController::class, 'destroy'], ['order' => $order]))
        ->assertNotFound();

    expect(Order::query()->find($order->id))->not->toBeNull();
});

test('user can still delete their own order without offers', function () {
    $owner = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'status' => OrderStatusEnum::New,
        'provider_id' => null,
        'accepted_offer_id' => null,
    ]);

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->deleteJson(action([OrderController::class, 'destroy'], ['order' => $order]))
        ->assertOk();

    expect(Order::query()->find($order->id))->toBeNull();
});
