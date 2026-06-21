<?php

namespace Modules\Wallet\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\DTOs\WalletTransactionData;
use Modules\Wallet\Enums\TransactionTypeEnum;

class CreditWalletAction
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepo,
        private readonly WalletTransactionRepositoryInterface $transactionRepo,
    ) {}

    public function handle(Model $owner, float $amount, Model $operation, string $description = ''): void
    {
        $wallet = $this->walletRepo->lockForUpdate($owner);
        $balanceBefore = (float) $wallet->balance;
        $wallet->increment('balance', $amount);

        $this->transactionRepo->create($wallet, $owner, new WalletTransactionData(
            amount: $amount,
            description: $description ?: 'Credit for '.$operation::class.'#'.$operation->getKey(),
            operation_type: $operation::class,
            operation_id: (string) $operation->getKey(),
            type: TransactionTypeEnum::Credit,
            credit: $amount,
            balance_before: $balanceBefore,
            balance_after: $balanceBefore + $amount,
        ));
    }
}
