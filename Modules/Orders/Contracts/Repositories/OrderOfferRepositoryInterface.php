<?php

namespace Modules\Orders\Contracts\Repositories;

use App\Models\Provider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;

interface OrderOfferRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Order $order, array $data): OrderOffer;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(OrderOffer $offer, array $data): OrderOffer;

    public function delete(OrderOffer $offer): void;

    public function paginateForProvider(Provider $provider, int $perPage): LengthAwarePaginator;
}
