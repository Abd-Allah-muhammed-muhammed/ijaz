<?php

namespace Modules\Chat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Modules\Chat\Actions\MigrateChatAttachmentsToMediaLibraryAction;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'chat:migrate-attachments-to-medialibrary')]
class MigrateChatAttachmentsToMediaLibraryCommand extends Command
{
    protected $signature = 'chat:migrate-attachments-to-medialibrary
                            {--dry-run : Report how many rows would be migrated without writing}';

    protected $description = 'Register existing conversation_attachments files into Spatie MediaLibrary (idempotent; does not delete legacy rows)';

    public function handle(MigrateChatAttachmentsToMediaLibraryAction $action): int
    {
        if (! Schema::hasTable('conversation_attachments')) {
            $this->warn('conversation_attachments table is already gone — nothing to migrate.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $result = $action->handle($dryRun);

        if ($result['total'] === 0) {
            $this->info('Nothing to migrate — conversation_attachments is empty.');

            return self::SUCCESS;
        }

        $verb = $dryRun ? 'Would migrate' : 'Migrated';

        $this->info("Legacy rows considered: {$result['total']}");
        $this->info("{$verb}: {$result['migrated']}");
        $this->info("Skipped (already migrated): {$result['skipped']}");
        $this->info("Missing file or orphaned message: {$result['missing']}");

        if ($dryRun) {
            $this->warn('Dry run — no MediaLibrary rows written. Legacy conversation_attachments rows left intact.');
        } else {
            $this->comment('Legacy conversation_attachments rows preserved for verification. Drop in a later migration after confirming MediaLibrary copies.');
        }

        return self::SUCCESS;
    }
}
