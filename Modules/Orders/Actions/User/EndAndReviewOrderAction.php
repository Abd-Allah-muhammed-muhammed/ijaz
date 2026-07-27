<?php

namespace Modules\Orders\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Orders\DTOs\EndAndReviewDTO;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Reviews\Services\ReviewService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EndAndReviewOrderAction
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {}

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
            $this->reviewService->submit(
                reviewer: $user,
                reviewee: $order->provider,
                operation: $order,
                rating: $data->rating,
                comment: $data->comment,
            );
        });
    }
}
