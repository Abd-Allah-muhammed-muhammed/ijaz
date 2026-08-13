<?php

namespace Modules\Orders\Console\Commands;

use Illuminate\Console\Command;
use Modules\Orders\Actions\SettleOrderPaymentAction;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'orders:settle-completed')]
class SettleCompletedOrdersCommand extends Command
{
    protected $description = 'Settle wallet holds for completed orders whose dispute window has elapsed';

    public function handle(
        OrderRepositoryInterface $orders,
        SettleOrderPaymentAction $settle,
    ): int {
        $hours = (int) app('settings')->get('order_dispute_window_hours', 48);
        $endedBefore = now()->subHours($hours);
        $count = 0;

        $orders->listDueForWalletSettlement($endedBefore)->each(function (Order $order) use ($settle, &$count): void {
            $settle->handle($order);
            $count++;
        });

        $this->info("Settled {$count} completed orders.");

        return self::SUCCESS;
    }
}
