<?php

use Modules\Orders\Actions\Provider\DeleteProviderOfferAction;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Exceptions\OrdersException;

it('deletes a pending offer belonging to the provider', function () {
    ['provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        offerAttrs: ['status' => OfferStatusEnum::Pending],
    );

    app(DeleteProviderOfferAction::class)->handle($order, $offer, $provider);

    expect($order->offers()->whereKey($offer->id)->exists())->toBeFalse();
});

it('blocks deleting an accepted offer', function () {
    ['provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        offerAttrs: ['status' => OfferStatusEnum::Accepted],
    );

    expect(fn () => app(DeleteProviderOfferAction::class)->handle($order, $offer, $provider))
        ->toThrow(OrdersException::class, 'you can not delete this offer because it has been processed.');

    expect($offer->fresh())->not->toBeNull();
});
