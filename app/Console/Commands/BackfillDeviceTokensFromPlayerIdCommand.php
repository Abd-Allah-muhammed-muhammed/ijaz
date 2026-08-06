<?php

namespace App\Console\Commands;

use App\Actions\DeviceToken\BackfillDeviceTokensFromPlayerIdAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'device-tokens:backfill-from-player-id')]
class BackfillDeviceTokensFromPlayerIdCommand extends Command
{
    protected $signature = 'device-tokens:backfill-from-player-id
                            {--dry-run : Report how many rows would be created without writing}';

    protected $description = 'Backfill users.player_id values into the polymorphic device_tokens table (chunked, idempotent)';

    public function handle(BackfillDeviceTokensFromPlayerIdAction $action): int
    {
        if (! Schema::hasColumn('users', 'player_id')) {
            $this->warn('users.player_id column is already gone — nothing to backfill.');

            return self::SUCCESS;
        }

        if (! Schema::hasTable('device_tokens')) {
            $this->error('device_tokens table does not exist. Run migrations first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $result = $action->handle($dryRun);

        if ($result->totalConsidered() === 0) {
            $this->info('Nothing to backfill — no non-null player_id values found.');

            return self::SUCCESS;
        }

        $verb = $dryRun ? 'Would migrate' : 'Migrated';

        $this->info("{$verb}: {$result->migrated}");
        $this->info("Skipped (already migrated): {$result->skipped}");
        $this->info("Conflicts (token already owned by another account): {$result->conflicts}");

        if ($dryRun) {
            $this->warn('Dry run — no rows written.');
        }

        return self::SUCCESS;
    }
}
