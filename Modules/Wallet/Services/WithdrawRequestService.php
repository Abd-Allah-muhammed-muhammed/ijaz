<?php

namespace Modules\Wallet\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Actions\Withdraw\CancelWithdrawRequestAction;
use Modules\Wallet\Actions\Withdraw\CreateWithdrawRequestAction;
use Modules\Wallet\Actions\Withdraw\ListAllWithdrawRequestsAction;
use Modules\Wallet\Actions\Withdraw\ListWithdrawRequestsForOwnerAction;
use Modules\Wallet\Actions\Withdraw\UpdateWithdrawStatusForDashboardAction;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Models\WithdrawRequest;

class WithdrawRequestService
{
    public function __construct(
        private readonly CreateWithdrawRequestAction $createAction,
        private readonly CancelWithdrawRequestAction $cancelAction,
        private readonly UpdateWithdrawStatusForDashboardAction $updateStatusForDashboardAction,
        private readonly ListWithdrawRequestsForOwnerAction $listForOwnerAction,
        private readonly ListAllWithdrawRequestsAction $listAllAction,
    ) {}

    /**
     * Create a withdraw request and hold pending debit.
     */
    public function create(Model $owner, CreateWithdrawData $data): WithdrawRequest
    {
        return DB::transaction(fn (): WithdrawRequest => $this->createAction->handle($owner, $data));
    }

    /**
     * Cancel a pending withdraw request and reverse pending debit.
     * Caller must wrap in DB::transaction().
     */
    public function cancel(Model $owner, WithdrawRequest $withdrawRequest): void
    {
        $this->cancelAction->handle($owner, $withdrawRequest);
    }

    /**
     * Admin dashboard approve/reject. Always finalizes the pending debit hold
     * (approve also debits balance; reject only clears pending).
     */
    public function updateStatusForDashboard(
        WithdrawRequest $withdrawRequest,
        string $status,
        ?string $adminNotes,
        int $adminId,
    ): WithdrawRequest {
        return $this->updateStatusForDashboardAction->handle(
            $withdrawRequest,
            $status,
            $adminNotes,
            $adminId,
        );
    }

    public function listForOwner(Model $owner, int $perPage = 16): LengthAwarePaginator
    {
        return $this->listForOwnerAction->handle($owner, $perPage);
    }

    public function listAll(int $perPage = 16): LengthAwarePaginator
    {
        return $this->listAllAction->handle($perPage);
    }
}
