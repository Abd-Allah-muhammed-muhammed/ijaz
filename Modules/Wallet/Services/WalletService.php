<?php

namespace Modules\Wallet\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Actions\AddPendingCreditAction;
use Modules\Wallet\Actions\AddPendingDebitAction;
use Modules\Wallet\Actions\AdjustPendingAction;
use Modules\Wallet\Actions\CreditWalletAction;
use Modules\Wallet\Actions\DebitWalletAction;
use Modules\Wallet\Actions\FinalizeWithdrawAction;
use Modules\Wallet\Actions\ReleasePendingCreditToBalanceAction;
use Modules\Wallet\Actions\ReversePendingCreditAction;
use Modules\Wallet\Actions\ReversePendingDebitAction;
use Modules\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use Modules\Wallet\DTOs\WalletBalanceData;
use Modules\Wallet\Models\WithdrawRequest;

class WalletService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepo,
        private readonly CreditWalletAction $creditAction,
        private readonly DebitWalletAction $debitAction,
        private readonly AddPendingCreditAction $addPendingCreditAction,
        private readonly AddPendingDebitAction $addPendingDebitAction,
        private readonly ReleasePendingCreditToBalanceAction $releasePendingCreditAction,
        private readonly ReversePendingDebitAction $reversePendingDebitAction,
        private readonly ReversePendingCreditAction $reversePendingCreditAction,
        private readonly AdjustPendingAction $adjustPendingAction,
        private readonly FinalizeWithdrawAction $finalizeWithdrawAction,
    ) {}

    public function credit(Model $owner, float $amount, Model $operation, string $description = ''): void
    {
        $this->creditAction->handle($owner, $amount, $operation, $description);
    }

    public function debit(Model $owner, float $amount, Model $operation, string $description = ''): void
    {
        $this->debitAction->handle($owner, $amount, $operation, $description);
    }

    public function addPendingCredit(Model $owner, float $amount, Model $operation, string $description = ''): void
    {
        $this->addPendingCreditAction->handle($owner, $amount, $operation, $description);
    }

    public function addPendingDebit(Model $owner, float $amount, Model $operation, string $description = ''): void
    {
        $this->addPendingDebitAction->handle($owner, $amount, $operation, $description);
    }

    public function releasePendingCreditToBalance(Model $owner, float $gross, float $net, Model $operation, string $description = ''): void
    {
        $this->releasePendingCreditAction->handle($owner, $gross, $net, $operation, $description);
    }

    public function reversePendingDebit(Model $owner, float $amount, Model $operation, string $description = ''): void
    {
        $this->reversePendingDebitAction->handle($owner, $amount, $operation, $description);
    }

    public function reversePendingCredit(Model $owner, float $amount, Model $operation, string $description = ''): void
    {
        $this->reversePendingCreditAction->handle($owner, $amount, $operation, $description);
    }

    public function adjustPending(Model $owner, float $creditDelta, float $debitDelta, Model $operation, string $description = ''): void
    {
        $this->adjustPendingAction->handle($owner, $creditDelta, $debitDelta, $operation, $description);
    }

    public function finalizeWithdraw(Model $owner, WithdrawRequest $request, bool $approved): void
    {
        $this->finalizeWithdrawAction->handle($owner, $request, $approved);
    }

    public function canWithdraw(Model $owner, float $amount): bool
    {
        $wallet = $this->walletRepo->lockForUpdate($owner);

        return ($wallet->balance - $wallet->pending_debit) >= $amount;
    }

    public function getBalance(Model $owner): WalletBalanceData
    {
        $wallet = $this->walletRepo->findOrCreate($owner);

        return WalletBalanceData::fromWallet($wallet);
    }
}
