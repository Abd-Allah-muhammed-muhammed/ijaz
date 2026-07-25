<?php

namespace Modules\Orders\Actions\Dashboard;

use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;

class CountAllOrdersAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(): int
    {
        return $this->orders->countAll();
    }
}
