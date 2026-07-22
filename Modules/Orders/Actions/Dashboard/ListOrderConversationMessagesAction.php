<?php

namespace Modules\Orders\Actions\Dashboard;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;

class ListOrderConversationMessagesAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(Order $order, int $perPage = 15): ?LengthAwarePaginator
    {
        return $this->orders->paginateConversationMessages($order, $perPage);
    }
}
