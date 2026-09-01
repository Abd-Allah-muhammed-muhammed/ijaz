<?php

use Illuminate\Support\Facades\Notification;
use Modules\Orders\Actions\Offer\ExpireStalePendingOrderOfferAction;
use Modules\Orders\Actions\Offer\UpdateOfferStatusAction;
use Modules\Orders\Actions\Provider\SubmitOfferAction;
use Modules\Orders\DTOs\StoreOrderOfferDTO;
use Modules\Orders\DTOs\UpdateOfferStatusDTO;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\OrderOffer;
use Modules\Orders\Notifications\OrderOfferCreatedNotification;
use Modules\Orders\Notifications\OrderOfferRejectedNotification;
use Modules\Settings\Models\Setting;
use Symfony\Component\HttpFoundation\Response;

beforeEach(function () {
    Notification::fake();
    setWalletSetting('order_offer_expiry_days', '7');
});

test('a provider cannot submit a second offer on the same order while their first is still Pending or Accepted', function (OfferStatusEnum $status) {
    ['provider' => $provider, 'order' => $order, 'offer' => $existingOffer] = createOrderWithOffer(
        orderAttrs: ['status' => OrderStatusEnum::New],
        offerAttrs: ['status' => $status],
    );

    expect(fn () => app(SubmitOfferAction::class)->handle(
        $order->fresh(),
        $provider,
        StoreOrderOfferDTO::fromValidated(['price' => 350.0, 'description' => 'Duplicate attempt']),
    ))->toThrow(OrdersException::class, 'provider_already_has_active_offer_on_order');

    expect($order->offers()->count())->toBe(1)
        ->and($order->offers()->first()->is($existingOffer))->toBeTrue();
})->with([
    'pending' => OfferStatusEnum::Pending,
    'accepted' => OfferStatusEnum::Accepted,
]);

test('a provider CAN submit a new offer after their previous one was Rejected, Cancelled, or the order reverted to New', function () {
    ['owner' => $owner, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        orderAttrs: ['status' => OrderStatusEnum::New],
        offerAttrs: ['status' => OfferStatusEnum::Rejected, 'price' => 200.0],
    );

    $replacement = app(SubmitOfferAction::class)->handle(
        $order->fresh(),
        $provider,
        StoreOrderOfferDTO::fromValidated(['price' => 225.0, 'description' => 'Fresh offer after rejection']),
    );

    expect($replacement->status)->toBe(OfferStatusEnum::Pending)
        ->and($order->offers()->count())->toBe(2);

    Notification::assertSentTo($owner, OrderOfferCreatedNotification::class);

    ['owner' => $ownerB, 'provider' => $providerB, 'order' => $orderB, 'offer' => $cancelledOffer] = createOrderWithOffer(
        orderAttrs: ['status' => OrderStatusEnum::New],
        offerAttrs: ['status' => OfferStatusEnum::Cancelled, 'price' => 180.0],
    );

    $afterCancel = app(SubmitOfferAction::class)->handle(
        $orderB->fresh(),
        $providerB,
        StoreOrderOfferDTO::fromValidated(['price' => 190.0, 'description' => 'Fresh offer after cancel']),
    );

    expect($afterCancel->status)->toBe(OfferStatusEnum::Pending);
    Notification::assertSentTo($ownerB, OrderOfferCreatedNotification::class);

    ['owner' => $ownerC, 'provider' => $providerC, 'order' => $orderC, 'offer' => $acceptedOffer] = createOrderWithOffer(
        orderAttrs: ['status' => OrderStatusEnum::OfferProvided, 'price' => 200],
        offerAttrs: ['status' => OfferStatusEnum::Accepted, 'price' => 200],
    );
    $orderC->update(['accepted_offer_id' => $acceptedOffer->id, 'provider_id' => $providerC->id]);

    app(UpdateOfferStatusAction::class)->handle(
        $orderC->fresh(),
        $acceptedOffer->fresh(),
        $ownerC,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Cancelled),
    );

    expect($orderC->fresh()->status)->toBe(OrderStatusEnum::New);

    $afterRevert = app(SubmitOfferAction::class)->handle(
        $orderC->fresh(),
        $providerC,
        StoreOrderOfferDTO::fromValidated(['price' => 210.0, 'description' => 'Fresh offer after order reopened']),
    );

    expect($afterRevert->status)->toBe(OfferStatusEnum::Pending);
    Notification::assertSentTo($ownerC, OrderOfferCreatedNotification::class);
});

test('submitting an offer on a non-New order is rejected with a clear error, not silently accepted', function (OrderStatusEnum $status) {
    ['provider' => $provider, 'order' => $order] = createOrderWithOffer(
        orderAttrs: ['status' => $status],
        offerAttrs: ['status' => OfferStatusEnum::Rejected],
    );

    expect(fn () => app(SubmitOfferAction::class)->handle(
        $order->fresh(),
        $provider,
        StoreOrderOfferDTO::fromValidated(['price' => 300.0, 'description' => 'Should not be allowed']),
    ))->toThrow(function (OrdersException $exception) {
        expect($exception->getTranslationKey())->toBe('order_must_be_new_to_submit_offer')
            ->and($exception->getHttpStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
    });

    expect($order->offers()->count())->toBe(1);
})->with([
    'offer provided' => OrderStatusEnum::OfferProvided,
    'in progress' => OrderStatusEnum::InProgress,
]);

test('a Pending offer older than the configured expiry window (default 7 days) is auto-expired by a scheduled job', function () {
    ['provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        orderAttrs: ['status' => OrderStatusEnum::New],
        offerAttrs: ['status' => OfferStatusEnum::Pending, 'price' => 250.0],
    );

    OrderOffer::query()->whereKey($offer->id)->update(['created_at' => now()->subDays(8)]);

    $this->artisan('orders:expire-pending-offers')
        ->assertSuccessful();

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Rejected);
});

test('an expired offer transitions to Rejected and notifies the provider', function () {
    ['provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        orderAttrs: ['status' => OrderStatusEnum::New],
        offerAttrs: ['status' => OfferStatusEnum::Pending, 'price' => 250.0],
    );

    OrderOffer::query()->whereKey($offer->id)->update(['created_at' => now()->subDays(10)]);

    app(ExpireStalePendingOrderOfferAction::class)->handle($offer->fresh());

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Rejected);

    Notification::assertSentTo($provider, OrderOfferRejectedNotification::class);
});

test('Accepted or already-terminal offers are never touched by the expiry job', function (OfferStatusEnum $status) {
    ['provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        orderAttrs: ['status' => OrderStatusEnum::OfferProvided, 'price' => 200],
        offerAttrs: ['status' => $status, 'price' => 200],
    );

    if ($status === OfferStatusEnum::Accepted) {
        $order->update(['accepted_offer_id' => $offer->id, 'provider_id' => $provider->id]);
    }

    OrderOffer::query()->whereKey($offer->id)->update(['created_at' => now()->subDays(30)]);

    $this->artisan('orders:expire-pending-offers')
        ->assertSuccessful();

    expect($offer->fresh()->status)->toBe($status);
    Notification::assertNothingSent();
})->with([
    'accepted' => OfferStatusEnum::Accepted,
    'rejected' => OfferStatusEnum::Rejected,
    'cancelled' => OfferStatusEnum::Cancelled,
    'paid' => OfferStatusEnum::Paid,
]);

test('changing the settings value changes the expiry window on the next job run, without a code deploy', function () {
    ['offer' => $youngOffer] = createOrderWithOffer(
        offerAttrs: ['status' => OfferStatusEnum::Pending, 'price' => 200.0],
    );
    OrderOffer::query()->whereKey($youngOffer->id)->update(['created_at' => now()->subDays(4)]);

    ['offer' => $oldOffer] = createOrderWithOffer(
        offerAttrs: ['status' => OfferStatusEnum::Pending, 'price' => 210.0],
    );
    OrderOffer::query()->whereKey($oldOffer->id)->update(['created_at' => now()->subDays(10)]);

    setWalletSetting('order_offer_expiry_days', '7');

    $this->artisan('orders:expire-pending-offers')->assertSuccessful();

    expect($youngOffer->fresh()->status)->toBe(OfferStatusEnum::Pending)
        ->and($oldOffer->fresh()->status)->toBe(OfferStatusEnum::Rejected);

    Setting::query()->where('key', 'order_offer_expiry_days')->update(['content' => '3']);
    cache()->forget('settings');
    app()->forgetInstance('settings');

    ['offer' => $midOffer] = createOrderWithOffer(
        offerAttrs: ['status' => OfferStatusEnum::Pending, 'price' => 220.0],
    );
    OrderOffer::query()->whereKey($midOffer->id)->update(['created_at' => now()->subDays(4)]);

    $this->artisan('orders:expire-pending-offers')->assertSuccessful();

    expect($midOffer->fresh()->status)->toBe(OfferStatusEnum::Rejected);
});

test('existing submit-offer flows (first offer on an order, offer after order reverts to New) are completely unaffected — regression', function () {
    ['owner' => $owner, 'provider' => $provider, 'order' => $order] = createOrderWithOffer(
        orderAttrs: ['status' => OrderStatusEnum::New],
        offerAttrs: ['status' => OfferStatusEnum::Pending],
    );

    $order->offers()->delete();

    $first = app(SubmitOfferAction::class)->handle(
        $order->fresh(),
        $provider,
        StoreOrderOfferDTO::fromValidated(['price' => 240.0, 'description' => 'First offer on order']),
    );

    expect($first->status)->toBe(OfferStatusEnum::Pending)
        ->and($order->offers()->count())->toBe(1);

    Notification::assertSentTo($owner, OrderOfferCreatedNotification::class);
});
