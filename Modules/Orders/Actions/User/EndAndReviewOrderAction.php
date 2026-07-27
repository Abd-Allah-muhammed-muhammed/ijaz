<?php

namespace Modules\Orders\Actions\User;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Orders\DTOs\EndAndReviewDTO;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Reviews\Models\Review;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EndAndReviewOrderAction
{
    /**
     * @throws Throwable
     */
    public function handle(Order $order, User $user, EndAndReviewDTO $data): void
    {
        if ($order->user()->isNot($user)) {
            abort(404);
        }
        if ($order->status->isNotIn([OrderStatusEnum::InProgress, OrderStatusEnum::EndedByProvider])) {
            throw new OrdersException('you can not end this order', Response::HTTP_BAD_REQUEST);
        }

        DB::transaction(function () use ($order, $user, $data) {
            $order->update(['status' => OrderStatusEnum::EndedByClient]);
            Review::updateOrCreate([
                'reviewer_type' => User::class,
                'reviewer_id' => $user->id,
                'operation_type' => Order::class,
                'operation_id' => $order->id,
            ], [
                'reviewee_type' => Provider::class,
                'reviewee_id' => $order->provider_id,
                'rating' => $data->rating,
                'comment' => $data->comment,
            ]);
        });
    }
}
