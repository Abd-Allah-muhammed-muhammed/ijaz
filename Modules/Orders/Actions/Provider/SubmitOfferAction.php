<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Contracts\Repositories\OrderOfferRepositoryInterface;
use Modules\Orders\DTOs\StoreOrderOfferDTO;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Modules\Orders\Notifications\OrderOfferCreatedNotification;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SubmitOfferAction
{
    public function __construct(
        private readonly OrderOfferRepositoryInterface $offers,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Order $order, Provider $provider, StoreOrderOfferDTO $data): OrderOffer
    {
        if ($order->status->isNot(OrderStatusEnum::New)) {
            throw new OrdersException('order_must_be_new_to_submit_offer', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->offers->providerHasActiveOffer($order, $provider)) {
            throw new OrdersException('provider_already_has_active_offer_on_order', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return DB::transaction(function () use ($order, $provider, $data) {
            $offer = $this->offers->create($order, [
                'provider_id' => $provider->id,
                'price' => $data->price,
                'description' => $data->description,
                'status' => 'pending',
                'user_id' => $order->user_id,
                'category_id' => $order->category_id,
            ]);

            $order->user->notify(new OrderOfferCreatedNotification($offer));

            return $offer;
        });
    }
}
