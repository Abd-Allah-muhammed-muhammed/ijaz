<?php

namespace Modules\Orders\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DeleteOrderMediaAction
{
    /**
     * @throws Throwable
     */
    public function handle(Order $order, Media $media, User $user): void
    {
        if ($order->user()->isNot($user)) {
            throw new OrdersException('forbidden !!', Response::HTTP_FORBIDDEN);
        }
        if ($media->model()->isNot($order)) {
            throw new OrdersException('forbidden !!', Response::HTTP_FORBIDDEN);
        }
        if ($order->status->isNot(OrderStatusEnum::New)) {
            throw new OrdersException('forbidden !!', Response::HTTP_FORBIDDEN);
        }

        DB::transaction(function () use ($media) {
            $media->delete();
        });
    }
}
