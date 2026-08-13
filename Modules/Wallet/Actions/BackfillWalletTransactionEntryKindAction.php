<?php

namespace Modules\Wallet\Actions;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\DTOs\WalletTransactionEntryKindBackfillResult;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\WithdrawRequest;

class BackfillWalletTransactionEntryKindAction
{
    private const SAMPLE_LIMIT = 15;

    public function __construct(
        private readonly WalletTransactionRepositoryInterface $transactions,
    ) {}

    /**
     * Classify withdraw/top-up ledger rows and rewrite description to the
     * matching translation key. Idempotent: unstamped rows (entry_kind IS NULL)
     * matching a known shape, plus already-stamped rows whose description is
     * not yet the key, are touched. Orders/Guarantor/bonus stay untouched.
     *
     * @param  Closure(int $processed, int $total): void|null  $onProgress
     */
    public function handle(
        bool $dryRun = false,
        int $chunkSize = 500,
        ?Closure $onProgress = null,
    ): WalletTransactionEntryKindBackfillResult {
        $approvedOperationIds = $this->transactions->withdrawOperationIdsWithDebit();

        $categories = $this->categories($approvedOperationIds);

        $total = 0;
        foreach ($categories as $category) {
            $total += $this->transactions->countUnstamped($category['constraints']);
            $total += $this->transactions->countStaleDescriptions($category['kind']);
        }

        $processed = 0;
        $counts = [];
        $samples = [];

        foreach ($categories as $name => $category) {
            $counts[$name] = 0;

            $this->transactions->chunkUnstampedById(
                $category['constraints'],
                $chunkSize,
                function (Collection $rows) use (
                    $dryRun,
                    $category,
                    $onProgress,
                    $total,
                    &$processed,
                    &$counts,
                    &$samples,
                    $name,
                ): void {
                    $this->collectSamples($rows, $category['kind'], $samples);
                    $counts[$name] += $rows->count();
                    $processed += $rows->count();

                    if (! $dryRun) {
                        $this->transactions->stampEntryKind(
                            $rows->modelKeys(),
                            $category['kind'],
                        );
                    }

                    if ($onProgress !== null) {
                        $onProgress($processed, $total);
                    }
                },
            );

            $this->transactions->chunkStaleDescriptionsById(
                $category['kind'],
                $chunkSize,
                function (Collection $rows) use (
                    $dryRun,
                    $category,
                    $onProgress,
                    $total,
                    &$processed,
                    &$counts,
                    &$samples,
                    $name,
                ): void {
                    $this->collectSamples($rows, $category['kind'], $samples);
                    $counts[$name] += $rows->count();
                    $processed += $rows->count();

                    if (! $dryRun) {
                        $this->transactions->stampDescriptionKey(
                            $rows->modelKeys(),
                            $category['kind'],
                        );
                    }

                    if ($onProgress !== null) {
                        $onProgress($processed, $total);
                    }
                },
            );
        }

        return new WalletTransactionEntryKindBackfillResult(
            withdrawRequested: $counts['withdraw_requested'] ?? 0,
            withdrawHoldReleased: $counts['withdraw_hold_released'] ?? 0,
            withdrawApproved: $counts['withdraw_approved'] ?? 0,
            withdrawRejected: $counts['withdraw_rejected'] ?? 0,
            withdrawCancelled: $counts['withdraw_cancelled'] ?? 0,
            topupCredited: $counts['topup_credited'] ?? 0,
            total: $total,
            dryRun: $dryRun,
            samples: $samples,
        );
    }

    /**
     * @param  Collection<int, WalletTransaction>  $rows
     * @param  list<array{0: string, 1: string}>  $samples
     */
    private function collectSamples(Collection $rows, WalletTransactionEntryKindEnum $kind, array &$samples): void
    {
        foreach ($rows as $row) {
            if (count($samples) >= self::SAMPLE_LIMIT) {
                return;
            }

            $samples[] = [
                (string) $row->getRawOriginal('description'),
                $kind->translationKey(),
            ];
        }
    }

    /**
     * @param  Collection<int, string>  $approvedOperationIds
     * @return array<string, array{kind: WalletTransactionEntryKindEnum, constraints: Closure(Builder): void}>
     */
    private function categories(Collection $approvedOperationIds): array
    {
        $withdrawType = WithdrawRequest::class;
        $topUpType = TopUpRequest::class;

        return [
            'withdraw_cancelled' => [
                'kind' => WalletTransactionEntryKindEnum::WithdrawCancelled,
                'constraints' => function (Builder $query) use ($withdrawType): void {
                    $query->where('operation_type', $withdrawType)
                        ->where('description', 'like', 'Withdraw Request Cancelled%');
                },
            ],
            'withdraw_requested' => [
                'kind' => WalletTransactionEntryKindEnum::WithdrawRequested,
                'constraints' => function (Builder $query) use ($withdrawType): void {
                    $query->where('operation_type', $withdrawType)
                        ->where('pending_debit', '>', 0);
                },
            ],
            'withdraw_approved' => [
                'kind' => WalletTransactionEntryKindEnum::WithdrawApproved,
                'constraints' => function (Builder $query) use ($withdrawType): void {
                    $query->where('operation_type', $withdrawType)
                        ->where('debit', '>', 0);
                },
            ],
            'withdraw_hold_released' => [
                'kind' => WalletTransactionEntryKindEnum::WithdrawHoldReleased,
                'constraints' => function (Builder $query) use ($withdrawType, $approvedOperationIds): void {
                    $query->where('operation_type', $withdrawType)
                        ->where('pending_debit', '<', 0);

                    if ($approvedOperationIds->isNotEmpty()) {
                        $query->whereIn('operation_id', $approvedOperationIds);
                    } else {
                        $query->whereRaw('0 = 1');
                    }
                },
            ],
            'withdraw_rejected' => [
                'kind' => WalletTransactionEntryKindEnum::WithdrawRejected,
                'constraints' => function (Builder $query) use ($withdrawType, $approvedOperationIds): void {
                    $query->where('operation_type', $withdrawType)
                        ->where('pending_debit', '<', 0)
                        ->where('description', 'like', 'Wallet withdraw for%');

                    if ($approvedOperationIds->isNotEmpty()) {
                        $query->whereNotIn('operation_id', $approvedOperationIds);
                    }
                },
            ],
            'topup_credited' => [
                'kind' => WalletTransactionEntryKindEnum::TopupCredited,
                'constraints' => function (Builder $query) use ($topUpType): void {
                    $query->where('operation_type', $topUpType)
                        ->where('credit', '>', 0);
                },
            ],
        ];
    }
}
