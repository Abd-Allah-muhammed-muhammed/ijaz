<?php

namespace Modules\Wallet\Services;

use App\Enums\OperationStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Exceptions\InsufficientBalanceException;
use Modules\Wallet\Exceptions\WalletException;
use Modules\Wallet\Models\WithdrawRequest;

class WithdrawRequestService
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    /**
     * Create a withdraw request and hold pending debit.
     * Caller must wrap in DB::transaction().
     */
    public function create(Model $owner, CreateWithdrawData $data): WithdrawRequest
    {
        if (! $this->walletService->canWithdraw($owner, $data->amount)) {
            throw new InsufficientBalanceException(
                available: $this->walletService->getBalance($owner)->available,
                requested: $data->amount,
            );
        }

        $withdrawRequest = $owner->withdrawRequests()->create([
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

    /**
     * Cancel a pending withdraw request and reverse pending debit.
     * Caller must wrap in DB::transaction().
     */
    public function cancel(Model $owner, WithdrawRequest $withdrawRequest): void
    {
        if (! $withdrawRequest->status->isPending()) {
            throw new WalletException('Only pending withdraw requests can be cancelled.');
        }

        $this->walletService->reversePendingDebit(
            owner: $owner,
            amount: (float) $withdrawRequest->amount,
            operation: $withdrawRequest,
            description: "Withdraw Request Cancelled #{$withdrawRequest->id}",
        );

        $withdrawRequest->delete();
    }

    public function listForOwner(Model $owner, int $perPage = 16): LengthAwarePaginator
    {
        return $owner->withdrawRequests()
            ->latest()
            ->paginate($perPage);
    }

    public function listAll(int $perPage = 16): LengthAwarePaginator
    {
        return WithdrawRequest::query()
            ->with('user')
            ->orderByRaw('status = ? DESC', [OperationStatusEnum::Pending->value])
            ->latest()
            ->paginate($perPage);
    }
}
