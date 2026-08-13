<?php

namespace Modules\Orders\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Orders\Actions\SettleOrderPaymentAction;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Models\Order;
use Symfony\Component\Console\Attribute\AsCommand;
use Throwable;

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
        $settled = 0;
        $skipped = 0;
        $failed = 0;

        $orders->listDueForWalletSettlement($endedBefore)->each(function (Order $order) use ($settle, &$settled, &$skipped, &$failed): void {
            try {
                $result = $settle->handle($order);

                if ($result->wallet_settled_at !== null) {
                    $settled++;
                } else {
                    $skipped++;
                }
            } catch (Throwable $e) {
                $failed++;
                report($e);
                Log::error('Order settlement failed', [
                    'order_id' => $order->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        });

        $this->info("Settled {$settled} completed orders.");

        if ($skipped > 0) {
            $this->warn("Skipped {$skipped} orders with insufficient pending holds.");
        }

        if ($failed > 0) {
            $this->error("Failed {$failed} orders. See the log for details.");
        }

        return self::SUCCESS;
    }
}
