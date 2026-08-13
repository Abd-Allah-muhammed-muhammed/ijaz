<?php

namespace Modules\Wallet\Console\Commands;

use Illuminate\Console\Command;
use Modules\Wallet\Actions\BackfillWalletTransactionEntryKindAction;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Stamp entry_kind and rewrite description to the matching translation key
 * on existing withdraw/top-up ledger rows. Safe for large production tables
 * (chunked, idempotent).
 *
 * Deploy: run MANUALLY after `php artisan migrate` — this is not invoked
 * automatically by migrations or the scheduler. Preview first, then write:
 *
 *   php artisan wallet:backfill-entry-kind --dry-run
 *   php artisan wallet:backfill-entry-kind
 */
#[AsCommand(name: 'wallet:backfill-entry-kind')]
class BackfillWalletTransactionEntryKindCommand extends Command
{
    protected $signature = 'wallet:backfill-entry-kind
                            {--dry-run : Report counts and sample description mappings without writing}
                            {--chunk=500 : Number of rows to process per chunk}';

    protected $description = 'Backfill wallet_transactions.entry_kind and description translation keys for historical withdraw/top-up rows (chunked, idempotent)';

    public function handle(BackfillWalletTransactionEntryKindAction $action): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));

        $result = $action->handle(
            dryRun: $dryRun,
            chunkSize: $chunkSize,
            onProgress: function (int $processed, int $total): void {
                $this->info("{$processed} / {$total} processed");
            },
        );

        $this->table(
            ['entry_kind', 'count'],
            [
                ['withdraw_requested', $result->withdrawRequested],
                ['withdraw_hold_released', $result->withdrawHoldReleased],
                ['withdraw_approved', $result->withdrawApproved],
                ['withdraw_rejected', $result->withdrawRejected],
                ['withdraw_cancelled', $result->withdrawCancelled],
                ['topup_credited', $result->topupCredited],
            ],
        );

        if ($dryRun && $result->samples !== []) {
            $this->table(['old description', 'new key'], $result->samples);
        }

        if ($result->processed() === 0) {
            $this->info('Nothing to backfill — no unstamped withdraw/top-up rows matched a known shape.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('Dry run — no rows updated. Re-run without --dry-run to write.');

            return self::SUCCESS;
        }

        $this->info("Stamped entry_kind and description on {$result->processed()} row(s).");

        return self::SUCCESS;
    }
}
