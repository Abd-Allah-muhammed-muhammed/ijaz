<?php

use App\Models\User;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Modules\Marketplace\Models\Category;
use Modules\Orders\Actions\Offer\UpdateOfferStatusAction;
use Modules\Orders\Actions\Provider\EndProviderOrderAction;
use Modules\Orders\Actions\Provider\SubmitOfferAction;
use Modules\Orders\Actions\User\CreateOrderAction;
use Modules\Orders\Database\Factories\OrderOfferFactory;
use Modules\Orders\DTOs\StoreOrderDTO;
use Modules\Orders\DTOs\StoreOrderOfferDTO;
use Modules\Orders\DTOs\UpdateOfferStatusDTO;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Events\NewOrderCreated;
use Modules\Orders\Models\Order;
use Modules\Orders\Notifications\NewOrderAssignNotification;
use Modules\Orders\Notifications\OrderAcceptedOfferPriceDecreasedNotification;
use Modules\Orders\Notifications\OrderCancelledNotification;
use Modules\Orders\Notifications\OrderCreatedConfirmationNotification;
use Modules\Orders\Notifications\OrderEndedByProviderNotification;
use Modules\Orders\Notifications\OrderOfferAcceptedNotification;
use Modules\Orders\Notifications\OrderOfferCanceledNotification;
use Modules\Orders\Notifications\OrderOfferCreatedNotification;
use Modules\Orders\Notifications\OrderOfferRejectedNotification;
use Modules\Orders\Notifications\OrderPaymentCompletedNotification;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;

beforeEach(function () {
    setWalletSetting('testing_fees', '20');
});

test('creating an order sends a confirmation notification to the requester (User)', function () {
    Notification::fake();
    Event::fake([NewOrderCreated::class]);

    $user = User::factory()->create();
    $category = Category::factory()->create();

    $order = app(CreateOrderAction::class)->handle($user, new StoreOrderDTO(
        attributes: [
            'title' => 'Need a service',
            'description' => 'Please help with this request',
            'category_id' => $category->id,
            'budget_start' => 100,
            'budget_end' => 200,
            'expected_time' => 7,
        ],
    ));

    Notification::assertSentTo($user, OrderCreatedConfirmationNotification::class, function (OrderCreatedConfirmationNotification $notification) use ($order) {
        return $notification->order->is($order);
    });
});

test('creating an assigned order still confirms the requester and notifies the provider', function () {
    Notification::fake();

    $user = User::factory()->create();
    $provider = createWalletProvider();
    $category = Category::factory()->create();

    $order = app(CreateOrderAction::class)->handle($user, new StoreOrderDTO(
        attributes: [
            'title' => 'Assigned service request',
            'description' => 'Directly assigned to a provider',
            'category_id' => $category->id,
            'provider_id' => $provider->id,
            'budget_start' => 100,
            'budget_end' => 200,
            'expected_time' => 7,
        ],
    ));

    Notification::assertSentTo($user, OrderCreatedConfirmationNotification::class);
    Notification::assertSentTo($provider, NewOrderAssignNotification::class, function (NewOrderAssignNotification $notification) use ($order) {
        return $notification->order->is($order);
    });
});

test('EndProviderOrderAction (provider marks work done) notifies the requester', function () {
    Notification::fake();

    ['user' => $user, 'provider' => $provider, 'order' => $order] = paidInProgressOrder();

    app(EndProviderOrderAction::class)->handle($order, $provider);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::EndedByProvider);

    Notification::assertSentTo($user, OrderEndedByProviderNotification::class);
    Notification::assertNotSentTo($provider, OrderEndedByProviderNotification::class);
});

test('payment completing (order transitions to InProgress) notifies BOTH the requester and the provider', function () {
    Notification::fake();

    ['user' => $user, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderPaymentContext(500.0);

    $payment = createPaymentFor($user, $offer, [
        'amount' => 500,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment));

    expect($order->fresh()->status)->toBe(OrderStatusEnum::InProgress);

    Notification::assertSentTo($user, OrderPaymentCompletedNotification::class);
    Notification::assertSentTo($provider, OrderPaymentCompletedNotification::class);
});

test('every user-facing Orders notification includes a screen key + relevant entity id in its Firebase data payload, matching Guarantor\'s established convention', function () {
    $cancelledOrder = Order::factory()->create([
        'status' => OrderStatusEnum::CancelledByClient,
        'cancellation_reason' => 'Provider did not start the work as agreed',
    ]);
    $offer = OrderOfferFactory::new()
        ->forOrder($cancelledOrder)
        ->forProvider(createWalletProvider())
        ->create();
    $cancelledOrder->update(['accepted_offer_id' => $offer->id]);
    $cancelledOrder->refresh();

    $endedOrder = Order::factory()->create(['status' => OrderStatusEnum::EndedByProvider]);
    $inProgressOrder = Order::factory()->inProgress()->create();
    $user = User::factory()->create(['language' => 'en']);

    $cases = [
        new OrderOfferCreatedNotification($offer),
        new OrderOfferAcceptedNotification($offer),
        new OrderOfferRejectedNotification($offer),
        new OrderOfferCanceledNotification($offer),
        new OrderCancelledNotification($cancelledOrder),
        new OrderCreatedConfirmationNotification($cancelledOrder),
        new OrderEndedByProviderNotification($endedOrder),
        new OrderPaymentCompletedNotification($inProgressOrder),
        new OrderAcceptedOfferPriceDecreasedNotification($cancelledOrder, 200.0, 175.0),
    ];

    foreach ($cases as $notification) {
        $data = $notification->toFirebase($user)->getData();

        expect($data)->toHaveKey('screen')
            ->and($data['screen'])->toBe('orders')
            ->and($data)->toHaveKey('order_id')
            ->and($data['order_id'])->not->toBeEmpty();
    }
});

test('OrderOfferCreatedNotification is actually queued/dispatched when SubmitOfferAction runs — full integration test from action call through to a queued notification job, not just asserting the notify() call happened', function () {
    Queue::fake();

    ['owner' => $owner, 'provider' => $provider, 'order' => $order] = createOrderWithOffer();

    // Drop the pre-seeded pending offer so SubmitOfferAction creates a fresh one.
    $order->offers()->delete();

    app(SubmitOfferAction::class)->handle(
        $order->fresh(),
        $provider,
        new StoreOrderOfferDTO(price: 250.0, description: 'Fresh price offer'),
    );

    Queue::assertPushed(SendQueuedNotifications::class, function ($job) use ($owner) {
        return $job->notification instanceof OrderOfferCreatedNotification
            && $job->notifiables->contains(fn ($notifiable) => $notifiable->is($owner));
    });
});

test('existing offer accept/reject/cancel notifications to the provider are completely unaffected — regression', function () {
    Notification::fake();

    ['owner' => $owner, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer();

    app(UpdateOfferStatusAction::class)->handle(
        $order,
        $offer,
        $owner,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Accepted),
    );

    Notification::assertSentTo($provider, OrderOfferAcceptedNotification::class);
    Notification::assertNotSentTo($owner, OrderOfferAcceptedNotification::class);

    Notification::fake();

    ['owner' => $owner, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer();

    app(UpdateOfferStatusAction::class)->handle(
        $order,
        $offer,
        $owner,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Rejected),
    );

    Notification::assertSentTo($provider, OrderOfferRejectedNotification::class);
    Notification::assertNotSentTo($owner, OrderOfferRejectedNotification::class);

    Notification::fake();

    ['owner' => $owner, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer();

    app(UpdateOfferStatusAction::class)->handle(
        $order,
        $offer,
        $owner,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Accepted),
    );

    app(UpdateOfferStatusAction::class)->handle(
        $order->fresh(),
        $offer->fresh(),
        $owner,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Cancelled),
    );

    Notification::assertSentTo($provider, OrderOfferCanceledNotification::class);
    Notification::assertNotSentTo($owner, OrderOfferCanceledNotification::class);
});
