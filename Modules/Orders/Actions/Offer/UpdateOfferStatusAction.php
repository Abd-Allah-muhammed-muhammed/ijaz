<?php

namespace Modules\Orders\Actions\Offer;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Actions\CalculateOrderFeesAction;
use Modules\Orders\Contracts\Repositories\OrderOfferRepositoryInterface;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\UpdateOfferStatusDTO;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Modules\Orders\Notifications\OrderOfferAcceptedNotification;
use Modules\Orders\Notifications\OrderOfferCanceledNotification;
use Modules\Orders\Notifications\OrderOfferRejectedNotification;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UpdateOfferStatusAction
{
    public function __construct(
        private readonly CalculateOrderFeesAction $calculateOrderFees,
        private readonly OrderRepositoryInterface $orders,
        private readonly OrderOfferRepositoryInterface $offers,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Order $order, OrderOffer $offer, User $user, UpdateOfferStatusDTO $data): void
    {
        if ($order->user()->isNot($user)) {
            abort(404);
        }

        if ($offer->status->isIn([OfferStatusEnum::Cancelled, OfferStatusEnum::Rejected, OfferStatusEnum::Paid]) || $offer->order()->isNot($order)) {
            throw new OrdersException('you can not update this offer', Response::HTTP_BAD_REQUEST);
        }

        if ($data->status->isIn([OfferStatusEnum::Pending, OfferStatusEnum::Paid])) {
            throw new OrdersException('order_offer_status_not_allowed', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::transaction(function () use ($order, $offer, $data): void {
            match ($data->status) {
                OfferStatusEnum::Accepted => $this->accept($order, $offer),
                OfferStatusEnum::Rejected => $this->reject($order, $offer),
                OfferStatusEnum::Cancelled => $this->cancel($order, $offer),
                default => throw new OrdersException('order_offer_status_not_allowed', Response::HTTP_UNPROCESSABLE_ENTITY),
            };
        });
    }

    private function accept(Order $order, OrderOffer $offer): void
    {
        $order = $this->orders->lockForUpdate($order);

        if ($order->status->isNot(OrderStatusEnum::New)) {
            throw new OrdersException('order_already_has_accepted_offer', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $offer->update(['status' => OfferStatusEnum::Accepted]);

        $fees = $this->calculateOrderFees->handle($order, (float) $offer->price);

        $this->orders->update($order, [
            'provider_id' => $offer->provider_id,
            'accepted_offer_id' => $offer->id,
            'status' => OrderStatusEnum::OfferProvided,
            'price' => $fees->price,
            'user_fees' => $fees->userFees,
            'provider_fees' => $fees->providerFees,
        ]);

        $this->notifyRejectedSiblings($this->offers->rejectPendingSiblings($order, $offer));

        $offer->provider->notify(new OrderOfferAcceptedNotification($offer));
    }

    private function reject(Order $order, OrderOffer $offer): void
    {
        $offer->update(['status' => OfferStatusEnum::Rejected]);

        if ($order->accepted_offer_id === $offer->id) {
            $this->revertOrderToNew($order);
        }

        $offer->provider->notify(new OrderOfferRejectedNotification($offer));
    }

    private function cancel(Order $order, OrderOffer $offer): void
    {
        $offer->update(['status' => OfferStatusEnum::Cancelled]);

        $this->revertOrderToNew($order);

        $offer->provider->notify(new OrderOfferCanceledNotification($offer));
    }

    private function revertOrderToNew(Order $order): void
    {
        $this->orders->update($order, [
            'provider_id' => null,
            'accepted_offer_id' => null,
            'status' => OrderStatusEnum::New,
            'price' => null,
        ]);
    }

    /**
     * @param  EloquentCollection<int, OrderOffer>  $siblings
     */
    private function notifyRejectedSiblings(EloquentCollection $siblings): void
    {
        foreach ($siblings as $sibling) {
            $sibling->provider->notify(new OrderOfferRejectedNotification($sibling));
        }
    }
}
