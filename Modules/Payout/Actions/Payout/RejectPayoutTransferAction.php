<?php

namespace Modules\Payout\Actions\Payout;

use Illuminate\Support\Facades\DB;
use Modules\Payout\Contracts\Repositories\PayoutRequestRepositoryInterface;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Exceptions\PayoutException;
use Modules\Payout\Models\PayoutRequest;

class RejectPayoutTransferAction
{
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

            if ($payoutRequest->submitted_by_admin_id === $adminId) {
                throw new PayoutException('payout.submitter_cannot_review');
            }

            return $this->repository->update($payoutRequest, [
                'status' => PayoutStatusEnum::Failed,
                'failure_reason' => $failureReason,
            ]);
        });
    }
}
