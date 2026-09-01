<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\Orders\Actions\User\EndAndReviewOrderAction;
use Modules\Orders\DTOs\EndAndReviewDTO;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Notifications\NewOrderAssignNotification;
use Modules\Orders\Notifications\OrderEndedByClientNotification;
use Modules\Orders\Notifications\OrderOfferAcceptedNotification;
use Modules\Orders\Notifications\OrderOfferCanceledNotification;
use Modules\Orders\Notifications\OrderOfferRejectedNotification;
use Modules\Reviews\Notifications\ReviewReceivedNotification;

beforeEach(function () {
    Notification::fake();
});

test('OrderOfferAcceptedNotification now sends Firebase to the provider, not just database+broadcast', function () {
    ['provider' => $provider, 'offer' => $offer] = createOrderWithOffer(
        offerAttrs: ['status' => OfferStatusEnum::Accepted],
    );

    $notification = new OrderOfferAcceptedNotification($offer);

    expect($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
        ->and($notification->toFirebase($provider)->getData())->toMatchArray([
            'order_id' => $offer->order_id,
            'offer_id' => $offer->id,
            'screen' => 'orders',
        ]);
});

test('OrderOfferRejectedNotification now sends Firebase to the provider', function () {
    ['provider' => $provider, 'offer' => $offer] = createOrderWithOffer();

    $notification = new OrderOfferRejectedNotification($offer);

    expect($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
        ->and($notification->toFirebase($provider)->getData()['screen'])->toBe('orders');
});

test('OrderOfferCanceledNotification now sends Firebase to the provider', function () {
    ['provider' => $provider, 'offer' => $offer] = createOrderWithOffer();

    $notification = new OrderOfferCanceledNotification($offer);

    expect($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
        ->and($notification->toFirebase($provider)->getData()['screen'])->toBe('orders');
});

test('NewOrderAssignNotification now sends Firebase to the assigned provider', function () {
    $provider = createWalletProvider();
    $order = Order::factory()->create(['provider_id' => $provider->id]);

    $notification = new NewOrderAssignNotification($order);

    expect($notification->via($provider))->toBe(['database', 'broadcast', 'firebase'])
        ->and($notification->toFirebase($provider)->getData())->toMatchArray([
            'order_id' => $order->id,
            'screen' => 'orders',
        ]);
});

test('EndAndReviewOrderAction (user ends + reviews) now notifies the provider — was previously completely silent', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = paidInProgressOrder();

    app(EndAndReviewOrderAction::class)->handle(
        $order,
        $user,
        new EndAndReviewDTO(rating: 4, comment: 'Solid work overall'),
    );

    expect($order->fresh()->status)->toBe(OrderStatusEnum::EndedByClient);

    Notification::assertSentTo($provider, OrderEndedByClientNotification::class);
});

test('the new provider notification includes the review rating/comment or at minimum the order id + screen deep-link data', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = paidInProgressOrder();

    app(EndAndReviewOrderAction::class)->handle(
        $order,
        $user,
        new EndAndReviewDTO(rating: 5, comment: 'Excellent service'),
    );

    Notification::assertSentTo($provider, OrderEndedByClientNotification::class, function (OrderEndedByClientNotification $notification) use ($provider, $order): bool {
        $firebase = $notification->toFirebase($provider)->getData();
        $array = $notification->toArray($provider);

        return $notification->order->is($order->fresh())
            && $notification->rating === 5
            && ($array['rating'] ?? null) === 5
            && ($firebase['order_id'] ?? null) === $order->id
            && ($firebase['rating'] ?? null) === '5'
            && ($firebase['screen'] ?? null) === 'orders';
    });
});

test('existing notification content (title/body/database payload) for all of these is unchanged — only the Firebase channel and the one new notification are new, not a content rewrite', function () {
    ['offer' => $offer] = createOrderWithOffer();
    $order = $offer->order;
    $user = User::factory()->create(['language' => 'en']);

    $expectedOfferNotifications = [
        OrderOfferAcceptedNotification::class => [
            'title_translated_key' => 'order_offer_accepted',
            'body_translated_key' => 'order_offer_has_been_accepted',
            'translated_attributes' => [],
            'order_id' => $offer->order_id,
            'offer_id' => $offer->id,
        ],
        OrderOfferRejectedNotification::class => [
            'title_translated_key' => 'order_offer_rejected',
            'body_translated_key' => 'order_offer_has_been_rejected',
            'translated_attributes' => [],
            'order_id' => $offer->order_id,
            'offer_id' => $offer->id,
        ],
        OrderOfferCanceledNotification::class => [
            'title_translated_key' => 'order_offer_canceled',
            'body_translated_key' => 'order_offer_has_been_canceled',
            'translated_attributes' => [],
            'order_id' => $offer->order_id,
            'offer_id' => $offer->id,
        ],
    ];

    foreach ($expectedOfferNotifications as $class => $expectedArray) {
        $notification = new $class($offer);

        expect($notification->toArray($user))->toBe($expectedArray)
            ->and($notification->toBroadcast($user)->data['title'])->toBe(trans($expectedArray['title_translated_key'], locale: 'en'))
            ->and($notification->toBroadcast($user)->data['body'])->toBe(trans($expectedArray['body_translated_key'], locale: 'en'));
    }

    $assignNotification = new NewOrderAssignNotification($order);

    expect($assignNotification->toArray($user))->toBe([
        'title_translated_key' => 'new_order_assigned',
        'body_translated_key' => 'you_have_been_assigned_a_new_order',
        'translated_attributes' => [],
        'order_id' => $order->id,
    ]);

    Notification::assertNothingSent();

    ['user' => $endingUser, 'provider' => $provider, 'order' => $endingOrder] = paidInProgressOrder();

    app(EndAndReviewOrderAction::class)->handle(
        $endingOrder,
        $endingUser,
        new EndAndReviewDTO(rating: 3, comment: 'Acceptable'),
    );

    Notification::assertSentTo($provider, OrderEndedByClientNotification::class);
    Notification::assertSentTo($provider, ReviewReceivedNotification::class);
});
