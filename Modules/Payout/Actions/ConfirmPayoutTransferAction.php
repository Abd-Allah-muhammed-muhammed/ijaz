<?php

namespace Modules\Payout\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Payout\Concerns\EnsuresPayoutReviewerIsNotSubmitter;
use Modules\Payout\Contracts\Repositories\PayoutRequestRepositoryInterface;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Exceptions\PayoutException;
use Modules\Payout\Models\PayoutRequest;

class ConfirmPayoutTransferAction
{
    use EnsuresPayoutReviewerIsNotSubmitter;

    public function __construct(
        private readonly PayoutRequestRepositoryInterface $repository,
    ) {}

    public function handle(PayoutRequest $payoutRequest, int $adminId): PayoutRequest
    {
        return DB::transaction(function () use ($payoutRequest, $adminId): PayoutRequest {
            $payoutRequest = $this->repository->lockForUpdate($payoutRequest);

            if ($payoutRequest->status === PayoutStatusEnum::Completed) {
                throw new PayoutException('payout.already_completed');
            }

            if ($payoutRequest->status !== PayoutStatusEnum::Submitted) {
                throw new PayoutException('payout.cannot_confirm_status');
            }

            $this->ensurePayoutReviewerIsNotSubmitter($payoutRequest, $adminId);

            return $this->repository->update($payoutRequest, [
                'status' => PayoutStatusEnum::Completed,
                'processed_by_admin_id' => $adminId,
                'failure_reason' => null,
            ]);
        });
    }
}
