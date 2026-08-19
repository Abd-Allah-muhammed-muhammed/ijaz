<?php

namespace Modules\Payout\Actions\Payout;

use Illuminate\Support\Facades\DB;
use Modules\Payout\Contracts\Repositories\PayoutRequestRepositoryInterface;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Exceptions\PayoutException;
use Modules\Payout\Models\PayoutRequest;

class ConfirmPayoutTransferAction
{
    public function __construct(
        private readonly PayoutRequestRepositoryInterface $repository,
    ) {}

    public function handle(PayoutRequest $payoutRequest, int $adminId, string $gatewayReference): PayoutRequest
    {
        return DB::transaction(function () use ($payoutRequest, $adminId, $gatewayReference): PayoutRequest {
            $payoutRequest = $this->repository->lockForUpdate($payoutRequest);

            if ($payoutRequest->status === PayoutStatusEnum::Completed) {
                throw new PayoutException('payout.already_completed');
            }

            if (! in_array($payoutRequest->status, [PayoutStatusEnum::Pending, PayoutStatusEnum::Failed], true)) {
                throw new PayoutException('payout.cannot_confirm_status');
            }

            if ($payoutRequest->maker_admin_id === $adminId) {
                throw new PayoutException('payout.maker_cannot_confirm');
            }

            return $this->repository->update($payoutRequest, [
                'status' => PayoutStatusEnum::Completed,
                'gateway_reference' => $gatewayReference,
                'processed_by_admin_id' => $adminId,
                'failure_reason' => null,
            ]);
        });
    }
}
