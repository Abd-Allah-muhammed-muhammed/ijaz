<?php

namespace Modules\Orders\Actions\Offer;

use App\Models\User;
use App\Notifications\Provider\OrderOfferAcceptedNotification;
use App\Notifications\Provider\OrderOfferCanceledNotification;
use App\Notifications\Provider\OrderOfferRejectedNotification;
use Illuminate\Support\Facades\DB;
use Modules\Orders\DTOs\UpdateOfferStatusDTO;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Modules\Payment\Services\PaymentService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UpdateOfferStatusAction
{
    public function __construct(
        private readonly PaymentService $paymentService,
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

        DB::transaction(function () use ($order, $offer, $data) {
            $offer->update([
                'status' => $data->status,
            ]);
            switch ($offer->status) {
                case OfferStatusEnum::Accepted:
                    if ($order->status->is(OrderStatusEnum::New)) {
                        $categoryFees = $order->category->getFees($offer->price);
                        $paymentGatewayFees = app('settings')->get($this->paymentService->getDefaultDriver().'_fees');
                        $fees = (float) $paymentGatewayFees + $categoryFees + (15 / 100 * $categoryFees);
                        $order->update([
                            'provider_id' => $offer->provider_id,
                            'accepted_offer_id' => $offer->id,
                            'status' => OrderStatusEnum::OfferProvided,
                            'price' => $offer->price,
                            'user_fees' => 0,
                            'provider_fees' => $fees,
                        ]);
                        $offer->provider->notify(new OrderOfferAcceptedNotification($offer));
                    }
                    break;
                case OfferStatusEnum::Rejected:
                    $offer->provider->notify(new OrderOfferRejectedNotification($offer));
                    break;
                case OfferStatusEnum::Pending:
                case OfferStatusEnum::Paid:
                    assert(false, 'unreachable');
                    // no break
                case OfferStatusEnum::Cancelled:
                    // KNOWN BUG: see Orders Step 2 — dead branch: $offer->status was just set to the
                    // incoming status above, so within the Cancelled case isNot(Cancelled) is always
                    // false and the cancel rollback logic never executes.
                    if ($offer->status->isNot(OfferStatusEnum::Cancelled)) {
                        $order->update([
                            'provider_id' => null,
                            'accepted_offer_id' => null,
                            'status' => OrderStatusEnum::New,
                            'price' => null,
                        ]);
                        $offer->provider->notify(new OrderOfferCanceledNotification($offer));
                    }
                    break;
            }
        });
    }
}
