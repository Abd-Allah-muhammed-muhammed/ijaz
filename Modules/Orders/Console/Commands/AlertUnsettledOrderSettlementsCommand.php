<?php

namespace Modules\Orders\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Orders\Actions\CountStuckUnsettledOrdersAction;
use Modules\Orders\Actions\NotifyAdminsOfStuckOrderSettlementsAction;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'orders:alert-unsettled')]
class AlertUnsettledOrderSettlementsCommand extends Command
{
    protected $description = 'Warn when paid ended orders remain unsettled past the dispute window';

    public function handle(
        CountStuckUnsettledOrdersAction $countStuck,
        NotifyAdminsOfStuckOrderSettlementsAction $notifyAdmins,
    ): int {
        $stuckCount = $countStuck->handle();

        if ($stuckCount === 0) {
            $this->info('No paid ended orders are stuck past the dispute window.');

            return self::SUCCESS;
        }

        Log::warning('Paid ended orders remain unsettled past the dispute window', [
            'stuck_count' => $stuckCount,
        ]);

        $notifyAdmins->handle($stuckCount);

        $this->warn("{$stuckCount} paid ended order(s) remain unsettled past the dispute window.");

        return self::SUCCESS;
    }
}
