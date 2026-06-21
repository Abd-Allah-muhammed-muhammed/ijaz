<?php

namespace Modules\Wallet\Actions;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\DTOs\WalletTransactionData;
use Modules\Wallet\Enums\TransactionTypeEnum;

class ReleasePendingCreditToBalanceAction
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepo,
        private readonly WalletTransactionRepositoryInterface $transactionRepo,
    ) {}

    public function handle(Model $owner, float $gross, float $net, Model $operation, string $description = ''): void
    {
        $wallet = $this->walletRepo->lockForUpdate($owner);
        $balanceBefore = (float) $wallet->balance;
        $wallet->decrement('pending_credit', $gross);
        $wallet->increment('balance', $net);

        $this->transactionRepo->create($wallet, $owner, new WalletTransactionData(
            amount: $net,
            description: $description ?: 'Release pending credit for '.$operation::class.'#'.$operation->getKey(),
            operation_type: $operation::class,
            operation_id: (string) $operation->getKey(),
            type: TransactionTypeEnum::Credit,
            credit: $net,
            pending_credit: -$gross,
            balance_before: $balanceBefore,
            balance_after: $balanceBefore + $net,
        ));
    }
}
