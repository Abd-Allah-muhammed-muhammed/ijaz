<?php

namespace Modules\Wallet\Actions\Withdraw;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Contracts\Repositories\WithdrawRequestRepositoryInterface;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Exceptions\WalletException;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WalletService;

class CancelWithdrawRequestAction
{
    public function __construct(
        private readonly WithdrawRequestRepositoryInterface $repository,
        private readonly WalletService $walletService,
    ) {}

    /**
     * Cancel a pending withdraw request and reverse pending debit.
     * Caller must wrap in DB::transaction().
     */
    public function handle(Model $owner, WithdrawRequest $withdrawRequest): void
    {
        if (! $withdrawRequest->status->isPending()) {
            throw new WalletException('Only pending withdraw requests can be cancelled.');
        }

        $this->walletService->reversePendingDebit(
            owner: $owner,
            amount: (float) $withdrawRequest->amount,
            operation: $withdrawRequest,
            description: "Withdraw Request Cancelled #{$withdrawRequest->id}",
            entryKind: WalletTransactionEntryKindEnum::WithdrawCancelled,
        );

        $this->repository->delete($withdrawRequest);
    }
}
