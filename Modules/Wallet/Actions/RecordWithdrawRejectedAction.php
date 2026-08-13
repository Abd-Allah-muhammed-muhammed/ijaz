<?php

namespace Modules\Wallet\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\DTOs\WalletTransactionData;
use Modules\Wallet\Enums\TransactionTypeEnum;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Models\WithdrawRequest;

class RecordWithdrawRejectedAction
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepo,
        private readonly WalletTransactionRepositoryInterface $transactionRepo,
    ) {}

    /**
     * Visible reject marker. The hold is already released by ReversePendingDebitAction;
     * this row must not change wallet balances a second time.
     */
    public function handle(Model $owner, WithdrawRequest $request, string $description): void
    {
        $wallet = $this->walletRepo->lockForUpdate($owner);
        $balanceBefore = (float) $wallet->balance;
        $amount = (float) $request->amount;

        $this->transactionRepo->create($wallet, $owner, new WalletTransactionData(
            amount: $amount,
            description: $description,
            operation_type: $request::class,
            operation_id: (string) $request->getKey(),
            type: TransactionTypeEnum::PendingDebit,
            pending_debit: -$amount,
            balance_before: $balanceBefore,
            balance_after: $balanceBefore,
            entry_kind: WalletTransactionEntryKindEnum::WithdrawRejected,
        ));
    }
}
