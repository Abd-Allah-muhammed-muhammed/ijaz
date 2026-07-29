<?php

namespace Modules\Orders\Actions\Provider;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Modules\Orders\DTOs\EndAndReviewDTO;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Reviews\Services\ReviewService;

class UpdateProviderReviewAction
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {}

    public function handle(Order $order, ?Authenticatable $authUser, EndAndReviewDTO $data): void
    {
        if ($order->status !== OrderStatusEnum::EndedByClient) {
            throw new OrdersException('you can not review this order');
        }

        if (! $authUser instanceof Model) {
            throw new OrdersException('you can not review this order');
        }

        // Reviewer is the authenticated provider; reviewee is the order's client user.
        $this->reviewService->submit(
            reviewer: $authUser,
            reviewee: $order->user,
            operation: $order,
            rating: $data->rating,
            comment: $data->comment,
        );
    }
}
