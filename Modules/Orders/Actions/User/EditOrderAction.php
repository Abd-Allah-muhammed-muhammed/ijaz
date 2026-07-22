<?php

namespace Modules\Orders\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\UpdateOrderDTO;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EditOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Order $order, User $user, UpdateOrderDTO $data): Order
    {
        if ($order->user()->isNot($user)) {
            throw new OrdersException('forbidden !!', Response::HTTP_FORBIDDEN);
        }
        if ($order->status->isNot(OrderStatusEnum::New)) {
            throw new OrdersException('forbidden !!', Response::HTTP_FORBIDDEN);
        }

        return DB::transaction(function () use ($order, $data) {
            $this->orders->update($order, $data->attributes);

            if (! empty($data->files)) {
                foreach ($data->files as $file) {
                    $order->addMedia($file)->toMediaCollection();
                }
            }
            //      $order->skills()->sync($data['skills']);
            $order->load(['media', 'skills.translation', 'city.translation', 'region.translation', 'category.translation']);

            return $order;
        });
    }
}
