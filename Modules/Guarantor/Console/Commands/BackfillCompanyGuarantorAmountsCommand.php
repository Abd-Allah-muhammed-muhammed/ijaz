<?php

namespace Modules\Guarantor\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Models\GuarantorRequest;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * One-time data fix: company guarantor creates mapped GuarantorData::amount from
 * validated('amount') which StoreCompanyGuarantorRequest never sends (it uses
 * total_amount). Affected rows have amount=0 so generated total = fees only.
 *
 * Sets amount = SUM(installments.amount) so the STORED GENERATED total column
 * recomputes correctly. Also fills empty titles from project_type (same DTO bug).
 */
#[AsCommand(name: 'guarantor:backfill-company-amounts')]
class BackfillCompanyGuarantorAmountsCommand extends Command
{
    protected $signature = 'guarantor:backfill-company-amounts
                            {--dry-run : Report how many rows would be updated without writing}';

    protected $description = 'Backfill company guarantor amount from installment sums (and empty titles from project_type)';

    public function handle(): int
    {
        $query = GuarantorRequest::query()
            ->where('type', GuarantorTypeEnum::Company)
            ->where('amount', 0);

        $affected = (clone $query)->count();

        $this->info("Company guarantor rows with amount=0: {$affected}");

        if ($affected === 0) {
            $this->info('Nothing to backfill.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->warn('Dry run — no rows updated.');

            return self::SUCCESS;
        }

        $updatedAmounts = 0;
        $updatedTitles = 0;
        $skippedNoInstallments = 0;

        $query->orderBy('id')->each(function (GuarantorRequest $request) use (&$updatedAmounts, &$updatedTitles, &$skippedNoInstallments): void {
            $sum = (float) $request->installments()->sum('amount');

            if ($sum <= 0) {
                $skippedNoInstallments++;

                return;
            }

            $fees = (float) $request->fees;
            $payload = ['amount' => $sum];

            // MySQL `total` is STORED GENERATED and recomputes from amount+fees.
            // SQLite uses a plain column that must be written explicitly.
            if (DB::getDriverName() === 'sqlite') {
                $payload['total'] = $sum + $fees;
            }

            DB::table('guarantor_requests')
                ->where('id', $request->id)
                ->update($payload);

            $updatedAmounts++;

            if ($request->title === '' || $request->title === null) {
                $title = filled($request->project_type) ? (string) $request->project_type : 'Company guarantor';

                DB::table('guarantor_requests')
                    ->where('id', $request->id)
                    ->update(['title' => $title]);

                $updatedTitles++;
            }
        });

        $this->info("Updated amount on {$updatedAmounts} row(s).");
        $this->info("Updated empty title on {$updatedTitles} row(s).");

        if ($skippedNoInstallments > 0) {
            $this->warn("Skipped {$skippedNoInstallments} row(s) with no installment sum.");
        }

        return self::SUCCESS;
    }
}
