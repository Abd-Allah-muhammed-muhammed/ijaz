<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Models\WithdrawRequest;

return new class extends Migration
{
    /**
     * Stamp entry_kind on existing withdraw/top-up ledger rows. Other operation
     * types (Orders, Guarantor, bonus, legacy) stay NULL.
     */
    public function up(): void
    {
        $withdrawType = WithdrawRequest::class;
        $topUpType = TopUpRequest::class;

        DB::table('wallet_transactions')
            ->where('operation_type', $withdrawType)
            ->where('description', 'like', 'Withdraw Request Cancelled%')
            ->update(['entry_kind' => WalletTransactionEntryKindEnum::WithdrawCancelled->value]);

        DB::table('wallet_transactions')
            ->where('operation_type', $withdrawType)
            ->where('pending_debit', '>', 0)
            ->update(['entry_kind' => WalletTransactionEntryKindEnum::WithdrawRequested->value]);

        DB::table('wallet_transactions')
            ->where('operation_type', $withdrawType)
            ->where('debit', '>', 0)
            ->update(['entry_kind' => WalletTransactionEntryKindEnum::WithdrawApproved->value]);

        $approvedOperationIds = DB::table('wallet_transactions')
            ->where('operation_type', $withdrawType)
            ->where('debit', '>', 0)
            ->pluck('operation_id');

        if ($approvedOperationIds->isNotEmpty()) {
            DB::table('wallet_transactions')
                ->where('operation_type', $withdrawType)
                ->where('pending_debit', '<', 0)
                ->whereIn('operation_id', $approvedOperationIds)
                ->update(['entry_kind' => WalletTransactionEntryKindEnum::WithdrawHoldReleased->value]);
        }

        DB::table('wallet_transactions')
            ->where('operation_type', $withdrawType)
            ->where('pending_debit', '<', 0)
            ->where('description', 'like', 'Wallet withdraw for%')
            ->when(
                $approvedOperationIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn('operation_id', $approvedOperationIds),
            )
            ->update(['entry_kind' => WalletTransactionEntryKindEnum::WithdrawRejected->value]);

        DB::table('wallet_transactions')
            ->where('operation_type', $topUpType)
            ->where('credit', '>', 0)
            ->update(['entry_kind' => WalletTransactionEntryKindEnum::TopupCredited->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('wallet_transactions')->update(['entry_kind' => null]);
    }
};
