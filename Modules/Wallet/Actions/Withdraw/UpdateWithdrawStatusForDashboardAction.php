<?php

namespace Modules\Wallet\Actions\Withdraw;

use App\Enums\OperationStatusEnum;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Contracts\Repositories\WithdrawRequestRepositoryInterface;
use Modules\Wallet\Exceptions\WalletException;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Notifications\WithdrawStatusChangedNotification;
use Modules\Wallet\Services\WalletService;

class UpdateWithdrawStatusForDashboardAction
{
    public function __construct(
        private readonly WithdrawRequestRepositoryInterface $repository,
        private readonly WalletService $walletService,
    ) {}

    public function handle(
        WithdrawRequest $withdrawRequest,
        string $status,
        ?string $adminNotes,
        int $adminId,
    ): WithdrawRequest {
        $approved = $status === OperationStatusEnum::Approved->value;

        return DB::transaction(function () use ($withdrawRequest, $status, $adminNotes, $adminId, $approved): WithdrawRequest {
            // Re-check under row lock so concurrent admin actions cannot double-finalize.
            $withdrawRequest = $this->repository->lockForUpdate($withdrawRequest);

            if ($withdrawRequest->status !== OperationStatusEnum::Pending) {
                throw new WalletException('wallet.cannot_update_withdraw_request_status');
            }

            $previousStatus = $withdrawRequest->status->value;

            $withdrawRequest = $this->repository->update($withdrawRequest, [
                'status' => $status,
                'admin_notes' => $adminNotes,
                'admin_id' => $adminId,
            ]);

            $this->walletService->finalizeWithdraw(
                owner: $withdrawRequest->user,
                request: $withdrawRequest,
                approved: $approved,
            );

            if (
                $previousStatus !== $status
                && WithdrawStatusChangedNotification::shouldNotify($status)
                && $withdrawRequest->user !== null
            ) {
                $withdrawRequest->user->notify(new WithdrawStatusChangedNotification(
                    withdrawRequest: $withdrawRequest,
                    status: $status,
                ));
            }

            return $withdrawRequest;
        });
    }
}
