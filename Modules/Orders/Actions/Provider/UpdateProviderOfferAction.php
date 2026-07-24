<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use App\Notifications\User\OrderAcceptedOfferUpdatedNotification;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Actions\CalculateOrderFeesAction;
use Modules\Orders\DTOs\UpdateOrderOfferDTO;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Throwable;

class UpdateProviderOfferAction
{
    public function __construct(
        private readonly CalculateOrderFeesAction $calculateOrderFees,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Order $order, OrderOffer $offer, Provider $provider, UpdateOrderOfferDTO $data): void
    {
        if ($offer->provider()->isNot($provider)) {
            abort(404);
        }
        if ($offer->status->isNotIn([OfferStatusEnum::Pending, OfferStatusEnum::Accepted])) {
            throw new OrdersException('you can not edit this offer because it has been processed.');
        }

        DB::transaction(function () use ($order, $offer, $data) {
            $offer->fill([
                'price' => $data->price,
                'description' => $data->description,
            ]);

            if (! $offer->isDirty()) {
                return;
            }

            $offer->update();
            if ($offer->status->is(OfferStatusEnum::Accepted) && $order->acceptedOffer()->is($offer)) {
                $fees = $this->calculateOrderFees->handle($order, (float) $offer->price);
                $order->update([
                    'price' => $fees->price,
                    'user_fees' => $fees->userFees,
                    'provider_fees' => $fees->providerFees,
                ]);
                $order->user->notify(new OrderAcceptedOfferUpdatedNotification($order));
            }
        });
    }
}
