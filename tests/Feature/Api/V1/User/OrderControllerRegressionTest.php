<?php

use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Marketplace\Enums\CategoryFeesTypeEnum;
use Modules\Marketplace\Models\Category;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Events\NewOrderCreated;
use Modules\Orders\Http\Controllers\Api\V1\OrderController;
use Modules\Orders\Models\Order;
use Modules\Orders\Notifications\OrderOfferAcceptedNotification;
use Modules\Orders\Notifications\OrderOfferCanceledNotification;
use Modules\Orders\Notifications\OrderOfferRejectedNotification;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\Services\PaymentService;
use Modules\Reviews\Models\Review;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Notification::fake();
    setWalletSetting('testing_fees', '20');
});

it('lists only the authenticated users orders with pagination', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Order::factory()->count(2)->create(['user_id' => $owner->id]);
    Order::factory()->create(['user_id' => $other->id]);

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->getJson(action([OrderController::class, 'index'], ['per_page' => 1]))
        ->assertOk()
        ->assertJsonPath('data.per_page', 1)
        ->assertJsonPath('data.total', 2);
});

it('stores an order, attaches media, and dispatches NewOrderCreated when unassigned', function () {
    Event::fake([NewOrderCreated::class]);
    $user = User::factory()->create();
    $category = Category::factory()->create();

    Sanctum::actingAs($user, ['user-api'], 'user-api');

    $response = $this->postJson(action([OrderController::class, 'store']), [
        'title' => 'Need plumber',
        'description' => 'Fix leak',
        'budget_start' => 100,
        'budget_end' => 300,
        'category_id' => $category->id,
        'files' => [UploadedFile::fake()->image('photo.jpg')],
    ]);

    $response->assertOk();
    $order = Order::query()->where('user_id', $user->id)->first();
    expect($order)->not->toBeNull()
        ->and($order->media)->toHaveCount(1);

    Event::assertDispatched(NewOrderCreated::class);
});

it('rejects store when validation fails', function () {
    Sanctum::actingAs(User::factory()->create(), ['user-api'], 'user-api');

    $this->postJson(action([OrderController::class, 'store']), [
        'title' => '',
        'budget_start' => 100,
        'budget_end' => 50,
    ])->assertUnprocessable();
});

/**
 * IDOR fix (Step 0.5): show() requires ownership — non-owners get 404
 * (same pattern as updateOfferStatus), without leaking order existence.
 */
it('returns 404 when a non-owner tries to view an order', function () {
    ['order' => $order] = createOrderWithOffer();
    $attacker = User::factory()->create();

    Sanctum::actingAs($attacker, ['user-api'], 'user-api');

    $this->getJson(action([OrderController::class, 'show'], ['order' => $order]))
        ->assertNotFound();
});

it('allows the owner to view their own order', function () {
    ['owner' => $owner, 'order' => $order] = createOrderWithOffer();

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->getJson(action([OrderController::class, 'show'], ['order' => $order]))
        ->assertOk()
        ->assertJsonPath('data.id', $order->id);
});

it('allows owner to edit an order while status is New', function () {
    ['owner' => $owner, 'order' => $order, 'category' => $category] = createOrderWithOffer();

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->postJson(action([OrderController::class, 'edit'], ['order' => $order]), [
        'title' => 'Updated title',
        'description' => 'Updated description',
        'budget_start' => 150,
        'budget_end' => 400,
        'category_id' => $category->id,
    ])->assertOk()
        ->assertJsonPath('data.title', 'Updated title');
});

it('forbids edit when order status is not New', function () {
    ['owner' => $owner, 'order' => $order, 'category' => $category] = createOrderWithOffer([
        'status' => OrderStatusEnum::InProgress,
    ]);

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->postJson(action([OrderController::class, 'edit'], ['order' => $order]), [
        'title' => 'Nope',
        'budget_start' => 150,
        'budget_end' => 400,
        'category_id' => $category->id,
    ])->assertForbidden();
});

it('forbids edit by non-owner', function () {
    ['order' => $order, 'category' => $category] = createOrderWithOffer();
    $attacker = User::factory()->create();

    Sanctum::actingAs($attacker, ['user-api'], 'user-api');

    $this->postJson(action([OrderController::class, 'edit'], ['order' => $order]), [
        'title' => 'Nope',
        'budget_start' => 150,
        'budget_end' => 400,
        'category_id' => $category->id,
    ])->assertForbidden();
});

/**
 * IDOR fix (Step 0.5): destroy() requires ownership before the "has offers" guard —
 * non-owners get 404 even when the order has no offers.
 */
it('returns 404 when a non-owner tries to delete an order', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'status' => OrderStatusEnum::New,
    ]);

    Sanctum::actingAs($attacker, ['user-api'], 'user-api');

    $this->deleteJson(action([OrderController::class, 'destroy'], ['order' => $order]))
        ->assertNotFound();

    expect(Order::query()->find($order->id))->not->toBeNull();
});

it('allows the owner to delete their own order when it has no offers', function () {
    $owner = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'status' => OrderStatusEnum::New,
    ]);

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->deleteJson(action([OrderController::class, 'destroy'], ['order' => $order]))
        ->assertOk();

    expect(Order::query()->find($order->id))->toBeNull();
});

it('rejects destroy when the order has offers', function () {
    ['owner' => $owner, 'order' => $order] = createOrderWithOffer();

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->deleteJson(action([OrderController::class, 'destroy'], ['order' => $order]))
        ->assertStatus(400)
        ->assertJsonPath('message', __('you can not delete this order because it has offers'));

    expect(Order::query()->find($order->id))->not->toBeNull();
});

it('deletes media for the owner while status is New', function () {
    ['owner' => $owner, 'order' => $order] = createOrderWithOffer();
    $order->addMedia(UploadedFile::fake()->image('a.jpg'))->toMediaCollection();
    $media = $order->media()->first();

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->deleteJson(action([OrderController::class, 'deleteMedia'], [
        'order' => $order,
        'media' => $media->uuid,
    ]))->assertOk();

    expect(Media::query()->find($media->id))->toBeNull();
});

it('forbids deleteMedia for non-owner', function () {
    ['order' => $order] = createOrderWithOffer();
    $order->addMedia(UploadedFile::fake()->image('a.jpg'))->toMediaCollection();
    $media = $order->media()->first();
    $attacker = User::factory()->create();

    Sanctum::actingAs($attacker, ['user-api'], 'user-api');

    $this->deleteJson(action([OrderController::class, 'deleteMedia'], [
        'order' => $order,
        'media' => $media->uuid,
    ]))->assertForbidden();
});

it('rejects updateOfferStatus for non-owner with 404 (IDOR lock from Pass 3)', function () {
    ['order' => $order, 'offer' => $offer] = createOrderWithOffer();
    $attacker = User::factory()->create();

    Sanctum::actingAs($attacker, ['user-api'], 'user-api');

    $this->postJson(action([OrderController::class, 'updateOfferStatus'], [
        'order' => $order,
        'offer' => $offer,
    ]), ['status' => OfferStatusEnum::Rejected->value])->assertNotFound();
});

it('accepts an offer and applies the exact fee formula with FIXED category fees', function () {
    // categoryFees=10 FIXED, gateway testing_fees=20, offer price=200
    // fees = 20 + 10 + (0.15 * 10) = 31.5
    ['owner' => $owner, 'order' => $order, 'offer' => $offer, 'provider' => $provider] = createOrderWithOffer(
        categoryAttrs: ['fees' => 10.0, 'fees_type' => CategoryFeesTypeEnum::FIXED],
        offerAttrs: ['price' => 200.0],
    );

    $expectedFees = computeUserControllerOfferFees(10.0, 20.0);
    expect($expectedFees)->toBe(31.5);

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->postJson(action([OrderController::class, 'updateOfferStatus'], [
        'order' => $order,
        'offer' => $offer,
    ]), ['status' => OfferStatusEnum::Accepted->value])
        ->assertOk();

    $order->refresh();
    $offer->refresh();

    expect($offer->status)->toBe(OfferStatusEnum::Accepted)
        ->and($order->status)->toBe(OrderStatusEnum::OfferProvided)
        ->and($order->provider_id)->toBe($provider->id)
        ->and($order->accepted_offer_id)->toBe($offer->id)
        ->and((float) $order->price)->toBe(200.0)
        ->and((float) $order->user_fees)->toBe(0.0)
        ->and((float) $order->provider_fees)->toBe(31.5);

    Notification::assertSentTo($provider, OrderOfferAcceptedNotification::class);
});

it('accepts an offer and applies the exact fee formula with PERCENTAGE category fees', function () {
    // categoryFees = 5% of 200 = 10, gateway=20 → fees = 20 + 10 + 1.5 = 31.5
    ['owner' => $owner, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        categoryAttrs: ['fees' => 5.0, 'fees_type' => CategoryFeesTypeEnum::PERCENTAGE],
        offerAttrs: ['price' => 200.0],
    );

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->postJson(action([OrderController::class, 'updateOfferStatus'], [
        'order' => $order,
        'offer' => $offer,
    ]), ['status' => OfferStatusEnum::Accepted->value])->assertOk();

    expect((float) $order->fresh()->provider_fees)->toBe(31.5);
});

it('rejects an offer and notifies the provider', function () {
    ['owner' => $owner, 'order' => $order, 'offer' => $offer, 'provider' => $provider] = createOrderWithOffer();

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->postJson(action([OrderController::class, 'updateOfferStatus'], [
        'order' => $order,
        'offer' => $offer,
    ]), ['status' => OfferStatusEnum::Rejected->value])->assertOk();

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Rejected);
    Notification::assertSentTo($provider, OrderOfferRejectedNotification::class);
});

/**
 * Cancel must reset order linkage fields and notify the provider.
 * (Previously dead: status was set to Cancelled before switch, so isNot(Cancelled) never ran.)
 */
it('cancels an accepted offer, resets order fields, and notifies the provider', function () {
    ['owner' => $owner, 'order' => $order, 'offer' => $offer, 'provider' => $provider] = createOrderWithOffer();

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->postJson(action([OrderController::class, 'updateOfferStatus'], [
        'order' => $order,
        'offer' => $offer,
    ]), ['status' => OfferStatusEnum::Accepted->value])->assertOk();

    expect($order->fresh()->provider_id)->toBe($provider->id)
        ->and($order->fresh()->accepted_offer_id)->toBe($offer->id)
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::OfferProvided);

    $this->postJson(action([OrderController::class, 'updateOfferStatus'], [
        'order' => $order,
        'offer' => $offer->fresh(),
    ]), ['status' => OfferStatusEnum::Cancelled->value])->assertOk();

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Cancelled)
        ->and($order->fresh()->provider_id)->toBeNull()
        ->and($order->fresh()->accepted_offer_id)->toBeNull()
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::New)
        ->and($order->fresh()->price)->toBeNull();

    Notification::assertSentTo($provider, OrderOfferCanceledNotification::class);
});

it('initiates payment via PaymentService using order user_total', function () {
    ['owner' => $owner, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        orderAttrs: [
            'status' => OrderStatusEnum::OfferProvided,
            'price' => 200,
            'user_fees' => 0,
            'provider_fees' => 31.5,
        ],
        offerAttrs: ['status' => OfferStatusEnum::Accepted, 'price' => 200],
    );
    $order->update(['accepted_offer_id' => $offer->id, 'provider_id' => $offer->provider_id]);

    $mock = Mockery::mock(PaymentService::class);
    $mock->shouldReceive('initiate')
        ->once()
        ->withArgs(function ($ownerArg, $productArg, $amount) use ($owner, $offer, $order) {
            return $ownerArg->is($owner)
                && $productArg->is($offer)
                && abs($amount - (float) $order->fresh()->user_total) < 0.001;
        })
        ->andReturn(new PaymentInitResult(
            status: 'success',
            driver: 'testing',
            url: 'https://pay.test/checkout',
            payable: true,
            transactionId: 'txn-1',
            message: null,
        ));
    app()->instance(PaymentService::class, $mock);

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->postJson(action([OrderController::class, 'pay'], [
        'order' => $order,
        'offer' => $offer,
    ]))->assertOk()
        ->assertJsonPath('data.url', 'https://pay.test/checkout');
});

it('ends an in-progress order and creates a review as the client', function () {
    $provider = createWalletProvider();
    ['owner' => $owner, 'order' => $order] = createOrderWithOffer([
        'status' => OrderStatusEnum::InProgress,
        'provider_id' => $provider->id,
    ]);

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->postJson(action([OrderController::class, 'endAndReview'], ['order' => $order]), [
        'rating' => 5,
        'comment' => 'Great work',
    ])->assertOk();

    $order->refresh();
    expect($order->status)->toBe(OrderStatusEnum::EndedByClient);

    $review = Review::query()->where([
        'reviewer_type' => User::class,
        'reviewer_id' => $owner->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
    ])->first();

    expect($review)->not->toBeNull()
        ->and($review->reviewee_type)->toBe(Provider::class)
        ->and($review->reviewee_id)->toBe($provider->id)
        ->and($review->rating)->toBe(5);
});

it('forbids endAndReview for non-owner with 404', function () {
    ['order' => $order] = createOrderWithOffer(['status' => OrderStatusEnum::InProgress]);
    $attacker = User::factory()->create();

    Sanctum::actingAs($attacker, ['user-api'], 'user-api');

    $this->postJson(action([OrderController::class, 'endAndReview'], ['order' => $order]), [
        'rating' => 4,
        'comment' => 'Nope',
    ])->assertNotFound();
});
