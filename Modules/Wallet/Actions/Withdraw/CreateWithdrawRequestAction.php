<?php

namespace Modules\Wallet\Actions\Withdraw;

use Illuminate\Database\Eloquent\Model;
use Modules\Wallet\Contracts\Repositories\WithdrawRequestRepositoryInterface;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Exceptions\InsufficientBalanceException;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WalletService;

class CreateWithdrawRequestAction
{
    public function __construct(
        private readonly WithdrawRequestRepositoryInterface $repository,
        private readonly WalletService $walletService,
    ) {}

    /**
     * Create a withdraw request and hold pending debit.
     * Caller must wrap in DB::transaction().
     */
    public function handle(Model $owner, CreateWithdrawData $data): WithdrawRequest
    {
        if (! $this->walletService->canWithdraw($owner, $data->amount)) {
            throw new InsufficientBalanceException(
                available: $this->walletService->getBalance($owner)->available,
                requested: $data->amount,
            );
        }

        $withdrawRequest = $this->repository->createForOwner($owner, [
            'amount' => $data->amount,
            'user_notes' => $data->userNotes,
        ]);

        $this->walletService->addPendingDebit(
            owner: $owner,
            amount: $data->amount,
            operation: $withdrawRequest,
            description: "Withdraw Request Created #{$withdrawRequest->id}",
        );

        return $withdrawRequest;
    }
}
