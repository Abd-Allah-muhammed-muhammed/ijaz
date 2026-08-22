<?php

namespace Modules\Payout\Actions\Payout;

use Illuminate\Support\Facades\DB;
use Modules\Payout\Actions\Payout\Concerns\EnsuresPayoutReviewerIsNotSubmitter;
use Modules\Payout\Contracts\Repositories\PayoutRequestRepositoryInterface;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Exceptions\PayoutException;
use Modules\Payout\Models\PayoutRequest;

class RejectPayoutTransferAction
{
    use EnsuresPayoutReviewerIsNotSubmitter;

    public function __construct(
        private readonly PayoutRequestRepositoryInterface $repository,
    ) {}

    /**
     * Review-reject a submitted payout's transfer evidence.
     * The submitting admin cannot reject their own submission.
     */
    public function handle(PayoutRequest $payoutRequest, int $adminId, string $failureReason): PayoutRequest
    {
        return DB::transaction(function () use ($payoutRequest, $adminId, $failureReason): PayoutRequest {
            $payoutRequest = $this->repository->lockForUpdate($payoutRequest);

            if ($payoutRequest->status !== PayoutStatusEnum::Submitted) {
                throw new PayoutException('payout.cannot_reject_status');
            }

            $this->ensurePayoutReviewerIsNotSubmitter($payoutRequest, $adminId);

            return $this->repository->update($payoutRequest, [
                'status' => PayoutStatusEnum::Failed,
                'failure_reason' => $failureReason,
            ]);
        });
    }
}
