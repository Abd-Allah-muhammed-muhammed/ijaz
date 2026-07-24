<?php

namespace Modules\Orders\Actions\Provider;

use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Orders\Contracts\Repositories\OrderOfferRepositoryInterface;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;

class DeleteProviderOfferAction
{
    public function __construct(
        private readonly OrderOfferRepositoryInterface $offers,
    ) {}

    public function handle(Order $order, OrderOffer $offer, ?Authenticatable $authUser): void
    {
        if ($offer->order()->isNot($order) || $offer->provider()->isNot($authUser)) {
            throw new OrdersException('sorry this offer does not belong to this order.');
        }
        // KNOWN BUG: see Orders Step 2 — ownership uses auth()->user() (default guard) instead of
        // auth('provider')->user(); the caller passes the default-guard user verbatim.
        if ($offer->status->isNot(OfferStatusEnum::Pending)) {
            throw new OrdersException('you can not delete this offer because it has been processed.');
        }

        $this->offers->delete($offer);
    }
}
