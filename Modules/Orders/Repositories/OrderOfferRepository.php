<?php

namespace Modules\Orders\Repositories;

use App\Models\Provider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Orders\Contracts\Repositories\OrderOfferRepositoryInterface;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;

class OrderOfferRepository implements OrderOfferRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Order $order, array $data): OrderOffer
    {
        return $order->offers()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(OrderOffer $offer, array $data): OrderOffer
    {
        $offer->update($data);

        return $offer;
    }

    public function delete(OrderOffer $offer): void
    {
        $offer->delete();
    }

    public function paginateForProvider(Provider $provider, int $perPage): LengthAwarePaginator
    {
        return $provider->orderOffers()
            ->with(['order'])
            ->latest()
            ->paginate($perPage);
    }
}
