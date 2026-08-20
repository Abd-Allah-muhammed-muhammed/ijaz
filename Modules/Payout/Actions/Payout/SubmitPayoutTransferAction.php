<?php

namespace Modules\Payout\Actions\Payout;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Payout\Contracts\Repositories\PayoutRequestRepositoryInterface;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Exceptions\PayoutException;
use Modules\Payout\Models\PayoutRequest;

class SubmitPayoutTransferAction
{
    public function __construct(
        private readonly PayoutRequestRepositoryInterface $repository,
    ) {}

    public function handle(
        PayoutRequest $payoutRequest,
        int $adminId,
        string $gatewayReference,
        UploadedFile $proofImage,
    ): PayoutRequest {
        return DB::transaction(function () use ($payoutRequest, $adminId, $gatewayReference, $proofImage): PayoutRequest {
            $payoutRequest = $this->repository->lockForUpdate($payoutRequest);

            if (! in_array($payoutRequest->status, [PayoutStatusEnum::Pending, PayoutStatusEnum::Failed], true)) {
                throw new PayoutException('payout.cannot_submit_status');
            }

            $payoutRequest = $this->repository->update($payoutRequest, [
                'status' => PayoutStatusEnum::Submitted,
                'gateway_reference' => $gatewayReference,
                'submitted_by_admin_id' => $adminId,
                'failure_reason' => null,
            ]);

            $payoutRequest->addMedia($proofImage)->toMediaCollection('transfer_proof', 'public');

            return $payoutRequest->fresh();
        });
    }
}
