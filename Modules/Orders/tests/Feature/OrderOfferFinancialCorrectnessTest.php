<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Mockery\MockInterface;
use Modules\Marketplace\Enums\CategoryFeesTypeEnum;
use Modules\Orders\Actions\Offer\InitiateOrderPaymentAction;
use Modules\Orders\Actions\Provider\UpdateProviderOfferAction;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\UpdateOrderOfferDTO;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Listeners\HandleOrderPaymentCompleted;
use Modules\Orders\Listeners\NotifyOrderPaymentFailed;
use Modules\Orders\Notifications\OrderAcceptedOfferPriceDecreasedNotification;
use Modules\Orders\Notifications\OrderAcceptedOfferPriceIncreaseBlockedNotification;
use Modules\Orders\Notifications\OrderPaymentAmountMismatchNotification;
use Modules\Orders\Notifications\OrderPaymentFailedNotification;
use Modules\Orders\Repositories\OrderRepository;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Services\PaymentService;

beforeEach(function () {
    Notification::fake();
    setWalletSetting('testing_fees', '20');
});

test('a price decrease after accept applies immediately and sends a notification with old and new amounts', function () {
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
    $order->update(['accepted_offer_id' => $offer->id, 'provider_id' => $provider->id]);

    app(UpdateProviderOfferAction::class)->handle(
        $order->fresh(),
        $offer->fresh(),
        $provider,
        UpdateOrderOfferDTO::fromValidated([
            'price' => 150.0,
            'description' => 'Discount applied',
        ]),
    );

    expect($order->fresh()->status)->toBe(OrderStatusEnum::OfferProvided)
        ->and((float) $order->fresh()->price)->toBe(150.0)
        ->and((float) $offer->fresh()->price)->toBe(150.0);

    Notification::assertSentTo($owner, OrderAcceptedOfferPriceDecreasedNotification::class);
    Notification::assertNotSentTo($owner, OrderAcceptedOfferPriceIncreaseBlockedNotification::class);
});

test('a price increase after accept does NOT apply immediately — order reverts to New, offer is cancelled, and sends a notification explaining why with old/attempted-new amounts', function () {
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
    );
    $order->update(['accepted_offer_id' => $offer->id, 'provider_id' => $provider->id]);

    app(UpdateProviderOfferAction::class)->handle(
        $order->fresh(),
        $offer->fresh(),
        $provider,
        UpdateOrderOfferDTO::fromValidated([
            'price' => 300.0,
            'description' => 'Attempted increase',
        ]),
    );

    expect($order->fresh()->status)->toBe(OrderStatusEnum::New)
        ->and($order->fresh()->accepted_offer_id)->toBeNull()
        ->and($order->fresh()->provider_id)->toBeNull()
        ->and($order->fresh()->price)->toBeNull()
        ->and($offer->fresh()->status)->toBe(OfferStatusEnum::Cancelled)
        ->and((float) $offer->fresh()->price)->toBe(200.0);

    Notification::assertSentTo($owner, OrderAcceptedOfferPriceIncreaseBlockedNotification::class);
    Notification::assertNotSentTo($owner, OrderAcceptedOfferPriceDecreasedNotification::class);
});

test('the price-change notification includes both amounts in its payload, not just a generic message', function () {
    ['owner' => $owner, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        orderAttrs: [
            'status' => OrderStatusEnum::OfferProvided,
            'price' => 200,
            'accepted_offer_id' => null,
        ],
        offerAttrs: ['status' => OfferStatusEnum::Accepted, 'price' => 200],
    );
    $order->update(['accepted_offer_id' => $offer->id, 'provider_id' => $provider->id]);

    app(UpdateProviderOfferAction::class)->handle(
        $order->fresh(),
        $offer->fresh(),
        $provider,
        UpdateOrderOfferDTO::fromValidated(['price' => 175.0, 'description' => 'Lower']),
    );

    Notification::assertSentTo($owner, OrderAcceptedOfferPriceDecreasedNotification::class, function (OrderAcceptedOfferPriceDecreasedNotification $notification) use ($owner): bool {
        $array = $notification->toArray($owner);
        $firebase = $notification->toFirebase($owner)->getData();

        return ($array['old_price'] ?? null) === '200.00'
            && ($array['new_price'] ?? null) === '175.00'
            && str_contains($notification->toBroadcast($owner)->data['body'], '200.00')
            && str_contains($notification->toBroadcast($owner)->data['body'], '175.00')
            && ($firebase['old_price'] ?? null) === '200.00'
            && ($firebase['new_price'] ?? null) === '175.00';
    });
});

test('UpdateProviderOfferAction now locks the order row during a post-accept price edit', function () {
    ['provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        orderAttrs: ['status' => OrderStatusEnum::OfferProvided, 'price' => 200],
        offerAttrs: ['status' => OfferStatusEnum::Accepted, 'price' => 200],
    );
    $order->update(['accepted_offer_id' => $offer->id, 'provider_id' => $provider->id]);

    /** @var OrderRepositoryInterface&MockInterface $repository */
    $repository = Mockery::mock(OrderRepository::class)->makePartial();
    app()->instance(OrderRepositoryInterface::class, $repository);

    app(UpdateProviderOfferAction::class)->handle(
        $order,
        $offer,
        $provider,
        UpdateOrderOfferDTO::fromValidated(['price' => 180.0, 'description' => 'Locked decrease']),
    );

    $repository->shouldHaveReceived('lockForUpdate')->once();
});

test('InitiateOrderPaymentAction locks the order row during initiation', function () {
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

    /** @var OrderRepositoryInterface&MockInterface $repository */
    $repository = Mockery::mock(OrderRepository::class)->makePartial();
    app()->instance(OrderRepositoryInterface::class, $repository);

    $mock = Mockery::mock(PaymentService::class);
    $mock->shouldReceive('initiate')->once()->andReturn(new PaymentInitResult(
        status: 'success',
        driver: 'testing',
        url: 'https://pay.test/checkout',
        payable: true,
        transactionId: 'txn-lock',
        message: null,
    ));
    app()->instance(PaymentService::class, $mock);

    app(InitiateOrderPaymentAction::class)->handle($order, $offer, $owner);

    $repository->shouldHaveReceived('lockForUpdate')->once();
});

test('a failed payment now sends a real notification to the user (NotifyOrderPaymentFailed no longer a no-op stub)', function () {
    ['user' => $user, 'offer' => $offer] = createOrderPaymentContext(500.0);

    $payment = createPaymentFor($user, $offer, [
        'amount' => 500,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Rejected,
    ]);

    app(NotifyOrderPaymentFailed::class)->handle(new PaymentFailed($payment));

    Notification::assertSentTo($user, OrderPaymentFailedNotification::class);
});

test('a payment that lands in NeedsReview due to amount mismatch notifies the user something needs attention', function () {
    ['user' => $user, 'order' => $order, 'offer' => $offer] = createOrderPaymentContext(500.0);

    $payment = createPaymentFor($user, $offer, [
        'amount' => 425,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    DB::transaction(fn () => app(HandleOrderPaymentCompleted::class)->handle(new PaymentCompleted($payment)));

    Notification::assertSentTo($user, OrderPaymentAmountMismatchNotification::class);
});

test('existing same-price offer edits (description-only, or price unchanged) are unaffected — regression', function () {
    ['owner' => $owner, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        orderAttrs: [
            'status' => OrderStatusEnum::OfferProvided,
            'price' => 200,
            'user_fees' => 0,
            'provider_fees' => 31.5,
        ],
        offerAttrs: ['status' => OfferStatusEnum::Accepted, 'price' => 200],
    );
    $order->update(['accepted_offer_id' => $offer->id, 'provider_id' => $provider->id]);

    app(UpdateProviderOfferAction::class)->handle(
        $order->fresh(),
        $offer->fresh(),
        $provider,
        UpdateOrderOfferDTO::fromValidated([
            'price' => 200.0,
            'description' => 'Clarified scope only',
        ]),
    );

    expect($order->fresh()->status)->toBe(OrderStatusEnum::OfferProvided)
        ->and((float) $offer->fresh()->price)->toBe(200.0)
        ->and($offer->fresh()->description)->toBe('Clarified scope only');

    Notification::assertNotSentTo($owner, OrderAcceptedOfferPriceDecreasedNotification::class);
    Notification::assertNotSentTo($owner, OrderAcceptedOfferPriceIncreaseBlockedNotification::class);
});

test('existing successful payment flows are unaffected — regression', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderPaymentContext(500.0);

    $payment = createPaymentFor($user, $offer, [
        'amount' => 500,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment));

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Paid)
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::InProgress)
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(500.0);

    Notification::assertNotSentTo($user, OrderPaymentAmountMismatchNotification::class);
});
