<?php

namespace Modules\Wallet\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\DTOs\WalletTransactionLifecycleData;
use Modules\Wallet\Enums\WalletTransactionLifecycleStatus;
use Modules\Wallet\Models\WalletTransaction;

class ListWalletTransactionLifecyclesAction
{
    public function __construct(
        private readonly WalletTransactionRepositoryInterface $transactionRepo,
    ) {}

    public function handle(
        Model $owner,
        int $perPage,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): LengthAwarePaginator {
        $groupedRows = $this->transactionRepo->listGroupedRowsForOwner(
            $owner,
            $perPage,
            $dateFrom,
            $dateTo,
        );

        $groupedRows->setCollection(
            $groupedRows->getCollection()->map(
                fn (Collection $rows): WalletTransactionLifecycleData => $this->toLifecycle($rows),
            ),
        );

        return $groupedRows;
    }

    /**
     * @param  Collection<int, WalletTransaction>  $rows
     */
    private function toLifecycle(Collection $rows): WalletTransactionLifecycleData
    {
        $hold = $rows->first(
            fn (WalletTransaction $row): bool => (float) $row->pending_debit > 0 || (float) $row->pending_credit > 0,
        );
        $reversal = $rows->first(
            fn (WalletTransaction $row): bool => (float) $row->pending_debit < 0 || (float) $row->pending_credit < 0,
        );
        $settled = $rows->first(
            fn (WalletTransaction $row): bool => (float) $row->debit > 0 || (float) $row->credit > 0,
        );

        if ($settled !== null) {
            $status = WalletTransactionLifecycleStatus::Completed;
            $representative = $settled;
        } elseif ($hold !== null && $reversal !== null) {
            $status = WalletTransactionLifecycleStatus::Rejected;
            $representative = $reversal;
        } else {
            $status = WalletTransactionLifecycleStatus::Pending;
            $representative = $hold ?? $rows->first();
        }

        $amountRow = $hold ?? $representative;

        return new WalletTransactionLifecycleData(
            operation_id: (string) $representative->operation_id,
            operation_type: (string) $representative->operation_type,
            status: $status,
            amount: $this->displayAmount($amountRow),
            balance_before: (float) $representative->balance_before,
            balance_after: (float) $representative->balance_after,
            description: (string) $representative->description,
            created_at: $representative->created_at,
        );
    }

    private function displayAmount(WalletTransaction $row): float
    {
        return max(
            abs((float) $row->credit),
            abs((float) $row->debit),
            abs((float) $row->pending_credit),
            abs((float) $row->pending_debit),
        );
    }
}
