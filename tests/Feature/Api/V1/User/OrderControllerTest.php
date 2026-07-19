<?php

use App\Enums\Order\OfferStatusEnum;
use App\Enums\Order\OrderStatusEnum;
use App\Http\Controllers\Api\V1\User\OrderController;
use App\Models\Order;
use App\Models\OrderOffer;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

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
