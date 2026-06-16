<?php

namespace Modules\Opportunity\Console\Commands;

use Illuminate\Console\Command;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\Jobs\ExpireOpportunityJob;
use Modules\Opportunity\Models\Opportunity;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'opportunities:expire')]
class ExpireOpportunitiesCommand extends Command
{
    protected $description = 'Dispatch jobs to expire opportunities past their expiry date';

    public function handle(OpportunityRepositoryInterface $repository): int
    {
        $count = 0;

        $repository->getExpired()->each(function (Opportunity $opportunity) use (&$count) {
            ExpireOpportunityJob::dispatch($opportunity)->onQueue('default');
            $count++;
        });

        $this->info("Dispatched {$count} expire jobs.");

        return self::SUCCESS;
    }
}
