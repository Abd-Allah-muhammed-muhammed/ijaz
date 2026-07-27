<?php

use Illuminate\Support\Facades\Notification;
use Modules\Orders\Actions\Offer\UpdateOfferStatusAction;
use Modules\Orders\DTOs\UpdateOfferStatusDTO;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Notifications\OrderOfferCanceledNotification;

beforeEach(function () {
    Notification::fake();
    setWalletSetting('testing_fees', '20');
});

it('fires OrderOfferCanceledNotification and resets order when cancelling an accepted offer', function () {
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

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Cancelled)
        ->and($order->fresh()->provider_id)->toBeNull()
        ->and($order->fresh()->accepted_offer_id)->toBeNull()
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::New)
        ->and($order->fresh()->price)->toBeNull();

    Notification::assertSentTo($provider, OrderOfferCanceledNotification::class);
});
