<?php

namespace Modules\Wallet\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\DTOs\WalletTransactionData;
use Modules\Wallet\Enums\TransactionTypeEnum;

class AdjustPendingAction
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepo,
        private readonly WalletTransactionRepositoryInterface $transactionRepo,
    ) {}

    public function handle(Model $owner, float $creditDelta, float $debitDelta, Model $operation, string $description = ''): void
    {
        $wallet = $this->walletRepo->lockForUpdate($owner);
        $balanceBefore = (float) $wallet->balance;

        $wallet->increment('pending_credit', $creditDelta, [
            'pending_debit' => DB::raw('pending_debit - '.$debitDelta),
        ]);

        $this->transactionRepo->create($wallet, $owner, new WalletTransactionData(
            amount: $creditDelta,
            description: $description ?: 'Adjust pending for '.$operation::class.'#'.$operation->getKey(),
            operation_type: $operation::class,
            operation_id: (string) $operation->getKey(),
            type: TransactionTypeEnum::PendingCredit,
            pending_credit: $creditDelta,
            pending_debit: -$debitDelta,
            balance_before: $balanceBefore,
            balance_after: $balanceBefore,
        ));
    }
}
