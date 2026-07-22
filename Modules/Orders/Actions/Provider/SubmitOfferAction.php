<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use App\Notifications\User\OrderOfferCreatedNotification;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Contracts\Repositories\OrderOfferRepositoryInterface;
use Modules\Orders\DTOs\StoreOrderOfferDTO;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
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
