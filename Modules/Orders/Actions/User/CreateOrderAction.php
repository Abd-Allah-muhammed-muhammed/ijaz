<?php

namespace Modules\Orders\Actions\User;

use App\Events\User\NewOrderCreated;
use App\Models\User;
use App\Notifications\Provider\NewOrderAssignNotification;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\StoreOrderDTO;
use Modules\Orders\Models\Order;
use Throwable;

class CreateOrderAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(User $user, StoreOrderDTO $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            $order = $this->orders->createForUser($user, $data->attributes);

            if (! empty($data->files)) {
                foreach ($data->files as $file) {
                    $order->addMedia($file)->toMediaCollection();
                }
            }
            //      $order->skills()->attach($data['skills']);
            if (empty($order->provider_id)) {
                $order->load('category.translation');
                NewOrderCreated::dispatch($order);
            } else {
                $order->provider->notify(new NewOrderAssignNotification($order));
            }
            $order->load(['media', 'skills.translation', 'city.translation', 'region.translation', 'category.translation']);

            return $order;
        });
    }
}
