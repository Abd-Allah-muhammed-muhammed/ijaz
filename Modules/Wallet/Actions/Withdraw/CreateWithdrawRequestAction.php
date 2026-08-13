<?php

namespace Modules\Wallet\Actions\Withdraw;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Actions\AddPendingDebitAction;
use Modules\Wallet\Contracts\Repositories\WalletRepositoryInterface;
use Modules\Wallet\Contracts\Repositories\WithdrawRequestRepositoryInterface;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Exceptions\InsufficientBalanceException;
use Modules\Wallet\Models\WithdrawRequest;

class CreateWithdrawRequestAction
{
    public function __construct(
        private readonly WithdrawRequestRepositoryInterface $repository,
        private readonly WalletRepositoryInterface $walletRepo,
        private readonly AddPendingDebitAction $addPendingDebitAction,
    ) {}

    /**
     * Create a withdraw request and hold pending debit.
     *
     * Balance check and pending-debit hold are serialized under a wallet row lock
     * (lockForUpdate) and an atomic compare-and-increment, so concurrent requests
     * cannot cumulatively exceed available balance.
     *
     * Caller must wrap in DB::transaction().
     */
    public function handle(Model $owner, CreateWithdrawData $data): WithdrawRequest
    {
        // Hold the wallet row for the remainder of the (caller's) transaction so
        // concurrent withdraw flows on MySQL/InnoDB wait here before reading balance.
        $wallet = $this->walletRepo->lockForUpdate($owner);

        $available = (float) $wallet->balance - (float) $wallet->pending_debit;
        if ($available < $data->amount) {
            throw new InsufficientBalanceException($available, $data->amount);
        }

        // Authoritative concurrent-safe hold BEFORE inserting the withdraw row.
        // Single UPDATE … WHERE available covers amount — works even when SELECT
        // FOR UPDATE is a no-op (SQLite). On failure the outer transaction rolls back.
        if (! $this->walletRepo->tryIncrementPendingDebitIfAvailable($wallet, $data->amount)) {
            $wallet->refresh();

            throw new InsufficientBalanceException(
                available: (float) $wallet->balance - (float) $wallet->pending_debit,
                requested: $data->amount,
            );
        }

        $withdrawRequest = $this->repository->createForOwner($owner, [
            'amount' => $data->amount,
            'user_notes' => $data->userNotes,
        ]);

        // Ledger only — pending_debit already held above.
        $this->addPendingDebitAction->handle(
            owner: $owner,
            amount: $data->amount,
            operation: $withdrawRequest,
            description: WalletTransactionEntryKindEnum::WithdrawRequested->translationKey(),
            requireSufficientAvailable: false,
            skipBalanceIncrement: true,
            entryKind: WalletTransactionEntryKindEnum::WithdrawRequested,
        );

        return $withdrawRequest;
    }
}
