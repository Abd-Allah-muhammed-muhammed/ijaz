<?php

namespace Modules\Orders\Services;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Orders\Actions\Offer\InitiateOrderPaymentAction;
use Modules\Orders\Actions\Offer\UpdateOfferStatusAction;
use Modules\Orders\Actions\Provider\DeleteProviderOfferAction;
use Modules\Orders\Actions\Provider\ListProviderOffersAction;
use Modules\Orders\Actions\Provider\SubmitOfferAction;
use Modules\Orders\Actions\Provider\UpdateProviderOfferAction;
use Modules\Orders\DTOs\StoreOrderOfferDTO;
use Modules\Orders\DTOs\UpdateOfferStatusDTO;
use Modules\Orders\DTOs\UpdateOrderOfferDTO;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Modules\Payment\DTOs\PaymentInitResult;
use Throwable;

class OrderOfferService
{
    public function __construct(
        private readonly UpdateOfferStatusAction $updateOfferStatus,
        private readonly InitiateOrderPaymentAction $initiateOrderPayment,
        private readonly SubmitOfferAction $submitOffer,
        private readonly UpdateProviderOfferAction $updateProviderOffer,
        private readonly ListProviderOffersAction $listProviderOffers,
        private readonly DeleteProviderOfferAction $deleteProviderOffer,
    ) {}

    /**
     * @throws Throwable
     */
    public function updateStatus(Order $order, OrderOffer $offer, User $user, UpdateOfferStatusDTO $data): void
    {
        $this->updateOfferStatus->handle($order, $offer, $user, $data);
    }

    /**
     * @throws Throwable
     */
    public function pay(Order $order, OrderOffer $offer, User $user): PaymentInitResult
    {
        return $this->initiateOrderPayment->handle($order, $offer, $user);
    }

    /**
     * @throws Throwable
     */
    public function submit(Order $order, Provider $provider, StoreOrderOfferDTO $data): OrderOffer
    {
        return $this->submitOffer->handle($order, $provider, $data);
    }

    /**
     * @throws Throwable
     */
    public function update(Order $order, OrderOffer $offer, Provider $provider, UpdateOrderOfferDTO $data): void
    {
        $this->updateProviderOffer->handle($order, $offer, $provider, $data);
    }

    public function listForProvider(Provider $provider, int $perPage): LengthAwarePaginator
    {
        return $this->listProviderOffers->handle($provider, $perPage);
    }

    public function deleteForProvider(Order $order, OrderOffer $offer, ?Authenticatable $authUser): void
    {
        $this->deleteProviderOffer->handle($order, $offer, $authUser);
    }
}
