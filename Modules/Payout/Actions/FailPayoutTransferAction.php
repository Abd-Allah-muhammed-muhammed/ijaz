<?php

namespace Modules\Payout\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Payout\Contracts\Repositories\PayoutRequestRepositoryInterface;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Exceptions\PayoutException;
use Modules\Payout\Models\PayoutRequest;

class FailPayoutTransferAction
{
    public function __construct(
        private readonly PayoutRequestRepositoryInterface $repository,
    ) {}

    /**
     * Direct-fail a pending payout that was never submitted (e.g. bad bank details).
     * No submitter/maker restriction.
     */
    public function handle(PayoutRequest $payoutRequest, string $failureReason): PayoutRequest
    {
        return DB::transaction(function () use ($payoutRequest, $failureReason): PayoutRequest {
            $payoutRequest = $this->repository->lockForUpdate($payoutRequest);

            if ($payoutRequest->status !== PayoutStatusEnum::Pending) {
                throw new PayoutException('payout.cannot_fail_status');
            }

            return $this->repository->update($payoutRequest, [
                'status' => PayoutStatusEnum::Failed,
                'failure_reason' => $failureReason,
            ]);
        });
    }
}
