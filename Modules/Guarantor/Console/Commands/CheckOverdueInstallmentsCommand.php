<?php

namespace Modules\Guarantor\Console\Commands;

use Illuminate\Console\Command;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Jobs\NotifyOverdueInstallmentJob;
use Modules\Guarantor\Models\GuarantorInstallment;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'guarantor:check-overdue')]
class CheckOverdueInstallmentsCommand extends Command
{
    protected $description = 'Check overdue installments and dispatch notification/release jobs';

    public function handle(InstallmentRepositoryInterface $repository): int
    {
        $count = 0;

        $repository->getOverdue()->each(function (GuarantorInstallment $installment) use (&$count) {
            NotifyOverdueInstallmentJob::dispatch($installment)
                ->onQueue('guarantor');
            $count++;
        });

        $this->info("Dispatched {$count} overdue installment jobs.");

        return self::SUCCESS;
    }
}
