<?php

namespace Modules\Orders\Actions;

use Carbon\CarbonInterface;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;

class CountStuckUnsettledOrdersAction
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
    ) {}

    public function handle(?CarbonInterface $endedBefore = null): int
    {
        $hours = (int) app('settings')->get('order_dispute_window_hours', 48);
        $endedBefore ??= now()->subHours($hours);

        return $this->orders->countDueForWalletSettlement($endedBefore);
    }
}
