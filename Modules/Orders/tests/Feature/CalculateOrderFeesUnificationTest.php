<?php

use App\Enums\CategoryFeesTypeEnum;
use Illuminate\Support\Facades\Notification;
use Modules\Orders\Actions\CalculateOrderFeesAction;
use Modules\Orders\Actions\Offer\UpdateOfferStatusAction;
use Modules\Orders\Actions\Provider\UpdateProviderOfferAction;
use Modules\Orders\DTOs\UpdateOfferStatusDTO;
use Modules\Orders\DTOs\UpdateOrderOfferDTO;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;

beforeEach(function () {
    Notification::fake();
    setWalletSetting('testing_fees', '20');
});

it('produces identical provider_fees from User accept and Provider update via the shared Action', function () {
    ['owner' => $owner, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        categoryAttrs: ['fees' => 10.0, 'fees_type' => CategoryFeesTypeEnum::FIXED],
        offerAttrs: ['price' => 200.0],
    );

    $sharedAt200 = app(CalculateOrderFeesAction::class)->handle($order, 200.0);
    expect($sharedAt200->providerFees)->toBe(31.5);

    app(UpdateOfferStatusAction::class)->handle(
        $order,
        $offer,
        $owner,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Accepted),
    );

    expect((float) $order->fresh()->provider_fees)->toBe($sharedAt200->providerFees)
        ->and($order->fresh()->status)->toBe(OrderStatusEnum::OfferProvided);

    app(UpdateProviderOfferAction::class)->handle(
        $order->fresh(),
        $offer->fresh(),
        $provider,
        UpdateOrderOfferDTO::fromValidated([
            'price' => 250.0,
            'description' => 'Price bump',
        ]),
    );

    $sharedAt250 = app(CalculateOrderFeesAction::class)->handle($order->fresh(), 250.0);

    expect((float) $order->fresh()->provider_fees)->toBe($sharedAt250->providerFees)
        ->and((float) $order->fresh()->provider_fees)->toBe(31.5)
        ->and((float) $order->fresh()->price)->toBe(250.0);
});
