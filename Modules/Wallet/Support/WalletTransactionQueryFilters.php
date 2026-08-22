<?php

namespace Modules\Wallet\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Models\WithdrawRequest;

final class WalletTransactionQueryFilters
{
    public static function withOperationForStatus(Builder $query): void
    {
        $query->with(['operation' => function (MorphTo $morphTo): void {
            $morphTo->morphWith([
                WithdrawRequest::class => ['payoutRequest'],
            ]);
        }]);
    }

    /**
     * Hide hold-release rows, and hide the original withdraw request once a
     * terminal sibling (approved / rejected / cancelled) exists for the group.
     */
    public static function excludeInternalWithdrawRows(Builder $query): void
    {
        $table = $query->getModel()->getTable();

        $query
            ->where(function (Builder $visible) use ($table): void {
                $visible->whereNull($table.'.entry_kind')
                    ->orWhere($table.'.entry_kind', '!=', WalletTransactionEntryKindEnum::WithdrawHoldReleased);
            })
            ->where(function (Builder $requested) use ($table): void {
                $requested->whereNull($table.'.entry_kind')
                    ->orWhere($table.'.entry_kind', '!=', WalletTransactionEntryKindEnum::WithdrawRequested)
                    ->orWhereNotExists(function ($siblings) use ($table): void {
                        $siblings->selectRaw('1')
                            ->from($table.' as entry_kind_siblings')
                            ->whereColumn('entry_kind_siblings.operation_id', $table.'.operation_id')
                            ->whereColumn('entry_kind_siblings.operation_type', $table.'.operation_type')
                            ->whereIn('entry_kind_siblings.entry_kind', [
                                WalletTransactionEntryKindEnum::WithdrawApproved,
                                WalletTransactionEntryKindEnum::WithdrawRejected,
                                WalletTransactionEntryKindEnum::WithdrawCancelled,
                            ]);
                    });
            });
    }
}
