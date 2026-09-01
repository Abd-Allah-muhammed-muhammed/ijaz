<?php

namespace Modules\Orders\Actions\Offer;

use Illuminate\Support\Facades\Log;
use Modules\Orders\Contracts\Repositories\OrderOfferRepositoryInterface;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Models\OrderOffer;
use Modules\Orders\Notifications\OrderOfferRejectedNotification;

class ExpireStalePendingOrderOfferAction
{
    public function __construct(
        private readonly OrderOfferRepositoryInterface $offers,
    ) {}

    public function handle(OrderOffer $offer): bool
    {
        $offer = $offer->fresh()->load('provider');

        if ($offer->status->isNot(OfferStatusEnum::Pending)) {
            return false;
        }

        $this->offers->update($offer, [
            'status' => OfferStatusEnum::Rejected,
        ]);

        $offer->provider->notify(new OrderOfferRejectedNotification($offer->fresh()));

        Log::info('Pending order offer expired — no response within configured window', [
            'order_offer_id' => $offer->id,
            'order_id' => $offer->order_id,
            'provider_id' => $offer->provider_id,
            'reason' => 'expired — no response within the window',
        ]);

        return true;
    }
}
