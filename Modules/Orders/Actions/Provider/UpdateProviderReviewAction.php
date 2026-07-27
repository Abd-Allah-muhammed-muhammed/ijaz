<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Orders\DTOs\EndAndReviewDTO;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Reviews\Models\Review;

class UpdateProviderReviewAction
{
    public function handle(Order $order, ?Authenticatable $authUser, EndAndReviewDTO $data): void
    {
        if ($order->status !== OrderStatusEnum::EndedByClient) {
            throw new OrdersException('you can not review this order');
        }

        // Reviewer is the authenticated provider; reviewee is the order's client user.
        Review::updateOrCreate([
            'reviewer_type' => Provider::class,
            'reviewer_id' => $authUser->id,
            'operation_type' => Order::class,
            'operation_id' => $order->id,
        ], [
            'reviewee_type' => User::class,
            'reviewee_id' => $order->user_id,
            'rating' => $data->rating,
            'comment' => $data->comment,
        ]);
    }
}
