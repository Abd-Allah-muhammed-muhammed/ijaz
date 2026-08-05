<?php

namespace Modules\Opportunity\Console\Commands;

use Illuminate\Console\Command;
use Modules\Opportunity\Actions\Opportunity\BackfillMissingOpportunityExpiryAction;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'opportunities:backfill-expiry')]
class BackfillOpportunityExpiryCommand extends Command
{
    protected $signature = 'opportunities:backfill-expiry
                            {--dry-run : Report how many rows would be updated without writing}';

    protected $description = 'Backfill null opportunity expires_at values (created_at + 7 days, or now + 7 if that would already be past)';

    public function handle(BackfillMissingOpportunityExpiryAction $action): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $count = $action->handle($dryRun);

        if ($count === 0) {
            $this->info('Nothing to backfill — no opportunities with null expires_at.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("Would backfill expires_at on {$count} opportunity row(s).");
            $this->warn('Dry run — no rows updated.');

            return self::SUCCESS;
        }

        $this->info("Backfilled expires_at on {$count} opportunity row(s).");

        return self::SUCCESS;
    }
}
