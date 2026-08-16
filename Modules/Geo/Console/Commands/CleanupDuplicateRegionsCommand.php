<?php

namespace Modules\Geo\Console\Commands;

use Illuminate\Console\Command;
use Modules\Geo\Actions\Region\CleanupDuplicateRegionsAction;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Delete the production-confirmed orphaned duplicate seeder regions (IDs 14–26).
 * Cities cascade-delete via cities.region_id. Safe to run twice.
 *
 * Deploy: run MANUALLY after deploy — this is not invoked by migrations or
 * the scheduler. Preview first, then write:
 *
 *   php artisan geo:cleanup-duplicate-regions --dry-run
 *   php artisan geo:cleanup-duplicate-regions
 */
#[AsCommand(name: 'geo:cleanup-duplicate-regions')]
class CleanupDuplicateRegionsCommand extends Command
{
    protected $signature = 'geo:cleanup-duplicate-regions
                            {--dry-run : Report what would be deleted without deleting anything}';

    protected $description = 'Delete production-confirmed orphaned duplicate seeder regions (IDs 14-26) and their cascade-deleted cities. Safe to run twice.';

    public function handle(CleanupDuplicateRegionsAction $action): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $result = $action->handle($dryRun);

        if ($result->regionCount === 0) {
            $this->info('Nothing to delete — regions 14-26 do not exist.');

            return self::SUCCESS;
        }

        foreach ($result->deleted as $row) {
            $this->line("Region {$row['id']}: {$row['title_ar']}");
        }

        if ($dryRun) {
            $this->info("Would delete {$result->regionCount} region(s) and {$result->cityCount} city/cities.");
            $this->warn('Dry run — no rows deleted.');

            return self::SUCCESS;
        }

        $this->info("Deleted {$result->regionCount} region(s) and {$result->cityCount} city/cities.");

        return self::SUCCESS;
    }
}
