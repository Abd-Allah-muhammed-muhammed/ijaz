<?php

namespace Modules\Payout\Actions\Payout;

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

    public function handle(PayoutRequest $payoutRequest, string $failureReason): PayoutRequest
    {
        return DB::transaction(function () use ($payoutRequest, $failureReason): PayoutRequest {
            $payoutRequest = $this->repository->lockForUpdate($payoutRequest);

            if ($payoutRequest->status === PayoutStatusEnum::Completed) {
                throw new PayoutException('payout.already_completed');
            }

            return $this->repository->update($payoutRequest, [
                'status' => PayoutStatusEnum::Failed,
                'failure_reason' => $failureReason,
            ]);
        });
    }
}
