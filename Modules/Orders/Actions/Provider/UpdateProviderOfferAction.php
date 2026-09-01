<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Actions\CalculateOrderFeesAction;
use Modules\Orders\Actions\Offer\RevertOrderToNewAction;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\UpdateOrderOfferDTO;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Modules\Orders\Notifications\OrderAcceptedOfferPriceDecreasedNotification;
use Modules\Orders\Notifications\OrderAcceptedOfferPriceIncreaseBlockedNotification;
use Throwable;

class UpdateProviderOfferAction
{
    public function __construct(
        private readonly CalculateOrderFeesAction $calculateOrderFees,
        private readonly OrderRepositoryInterface $orders,
        private readonly RevertOrderToNewAction $revertOrderToNew,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Order $order, OrderOffer $offer, Provider $provider, UpdateOrderOfferDTO $data): void
    {
        if ($offer->provider()->isNot($provider)) {
            abort(404);
        }
        if ($offer->order()->isNot($order)) {
            throw new OrdersException('sorry this offer does not belong to this order.');
        }
        if ($offer->status->isNotIn([OfferStatusEnum::Pending, OfferStatusEnum::Accepted])) {
            throw new OrdersException('you can not edit this offer because it has been processed.');
        }

        DB::transaction(function () use ($order, $offer, $provider, $data): void {
            $order = $this->orders->lockForUpdate($order);
            $offer = $offer->fresh();

            if ($offer->provider()->isNot($provider)) {
                abort(404);
            }
            if ($offer->order()->isNot($order)) {
                throw new OrdersException('sorry this offer does not belong to this order.');
            }
            if ($offer->status->isNotIn([OfferStatusEnum::Pending, OfferStatusEnum::Accepted])) {
                throw new OrdersException('you can not edit this offer because it has been processed.');
            }

            if ($offer->status->is(OfferStatusEnum::Accepted) && $order->accepted_offer_id === $offer->id) {
                $this->handleAcceptedOfferUpdate($order, $offer, $data);

                return;
            }

            $this->applyPendingOfferUpdate($offer, $data);
        });
    }

    private function handleAcceptedOfferUpdate(Order $order, OrderOffer $offer, UpdateOrderOfferDTO $data): void
    {
        $oldPrice = (float) $offer->price;
        $newPrice = (float) $data->price;

        if ($newPrice > $oldPrice) {
            $offer->update(['status' => OfferStatusEnum::Cancelled]);
            $this->revertOrderToNew->handle($order);
            $order->user->notify(new OrderAcceptedOfferPriceIncreaseBlockedNotification(
                order: $order->fresh(),
                offer: $offer->fresh(),
                oldPrice: $oldPrice,
                attemptedNewPrice: $newPrice,
            ));

            return;
        }

        $offer->fill([
            'price' => $data->price,
            'description' => $data->description,
        ]);

        if (! $offer->isDirty()) {
            return;
        }

        $offer->update();

        $fees = $this->calculateOrderFees->handle($order, (float) $offer->price);
        $this->orders->update($order, [
            'price' => $fees->price,
            'user_fees' => $fees->userFees,
            'provider_fees' => $fees->providerFees,
        ]);

        if ($newPrice < $oldPrice) {
            $order->user->notify(new OrderAcceptedOfferPriceDecreasedNotification(
                order: $order->fresh(),
                oldPrice: $oldPrice,
                newPrice: $newPrice,
            ));
        }
    }

    private function applyPendingOfferUpdate(OrderOffer $offer, UpdateOrderOfferDTO $data): void
    {
        $offer->fill([
            'price' => $data->price,
            'description' => $data->description,
        ]);

        if (! $offer->isDirty()) {
            return;
        }

        $offer->update();
    }
}
