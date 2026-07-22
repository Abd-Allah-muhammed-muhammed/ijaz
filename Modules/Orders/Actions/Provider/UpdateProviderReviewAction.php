<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Modules\Orders\DTOs\EndAndReviewDTO;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;

class UpdateProviderReviewAction
{
    public function handle(Order $order, ?Authenticatable $authUser, EndAndReviewDTO $data): void
    {
        if ($order->status !== OrderStatusEnum::EndedByClient) {
            throw new OrdersException('you can not review this order');
        }

        // KNOWN BUG: see Orders Step 2 — reviewer/reviewee crossover: reviewer_id is set to the order's
        // user_id (the client) while reviewee is the acting provider (auth()->user()), inverting the
        // intended provider→user review direction. auth()->user() also uses the default guard.
        Review::updateOrCreate([
            'reviewer_type' => Provider::class,
            'reviewer_id' => $order->user_id,
            'operation_type' => Order::class,
            'operation_id' => $order->id,
        ], [
            'reviewee_type' => User::class,
            'reviewee_id' => $authUser->id,
            'rating' => $data->rating,
            'comment' => $data->comment,
        ]);
    }
}
