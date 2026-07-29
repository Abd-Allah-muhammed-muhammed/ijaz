<?php

namespace Modules\Wallet\Actions\Withdraw;

use App\Enums\OperationStatusEnum;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Contracts\Repositories\WithdrawRequestRepositoryInterface;
use Modules\Wallet\Exceptions\WalletException;
use Modules\Wallet\Models\WithdrawRequest;
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
        if ($withdrawRequest->status !== OperationStatusEnum::Pending) {
            throw new WalletException('wallet.cannot_update_withdraw_request_status');
        }

        $approved = $status === OperationStatusEnum::Approved->value;

        return DB::transaction(function () use ($withdrawRequest, $status, $adminNotes, $adminId, $approved): WithdrawRequest {
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

            return $withdrawRequest;
        });
    }
}
