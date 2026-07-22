<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use App\Notifications\User\OrderAcceptedOfferUpdatedNotification;
use Illuminate\Support\Facades\DB;
use Modules\Orders\DTOs\UpdateOrderOfferDTO;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;
use Throwable;

class UpdateProviderOfferAction
{
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
                $categoryFees = $order->category->getFees($offer->price);
                // KNOWN BUG: see Orders Step 2 — provider offer update reads gateway fees via
                // config('payment.default') while the User controller uses PaymentService::getDefaultDriver().
                $paymentGatewayFees = app('settings')->get(config('payment.default').'_fees');
                $providerFees = floatval($paymentGatewayFees) + $categoryFees + (15 / 100 * $categoryFees);
                $order->update([
                    'price' => $offer->price,
                    'user_fees' => 0,
                    'provider_fees' => $providerFees,
                ]);
                $order->user->notify(new OrderAcceptedOfferUpdatedNotification($order));
            }
        });
    }
}
