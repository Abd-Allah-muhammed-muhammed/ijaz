<?php

namespace Modules\Guarantor\Actions\Guarantor;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction as LogGuarantorStatusHistory;
use Modules\Guarantor\Actions\Guarantor\ReverseGuarantorWalletHoldsAction as ReverseGuarantorWalletHolds;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorDisputeResolutionEnum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorDisputeResolvedNotification;
use Throwable;

class ResolveDisputeEscalateAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly LogGuarantorStatusHistory $logStatusHistory,
        private readonly ReverseGuarantorWalletHolds $reverseGuarantorWalletHoldsAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        GuarantorRequest $request,
        Admin $admin,
        ?string $notes = null,
    ): GuarantorRequest {
        return DB::transaction(function () use ($request, $admin, $notes) {
            if ($request->status->isNot(GuarantorStatusEnum::Disputed)) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $fromStatus = $request->status->value;
            $resolution = GuarantorDisputeResolutionEnum::Escalate;

            $guarantorRequest = $this->guarantorRepository->update($request, [
                'status' => GuarantorStatusEnum::Escalated,
            ]);

            $this->reverseGuarantorWalletHoldsAction->handle(
                $guarantorRequest->fresh(),
                'dispute escalated',
            );

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $admin,
                $fromStatus,
                GuarantorStatusEnum::Escalated->value,
                reason: $resolution->historyReason(),
                notes: $notes,
            );

            $guarantorRequest->loadMissing(['requester', 'counterparty']);
            $notification = new GuarantorDisputeResolvedNotification($guarantorRequest, $resolution);
            $guarantorRequest->requester?->notify($notification);
            $guarantorRequest->counterparty?->notify($notification);

            return $guarantorRequest->load(['requester', 'counterparty', 'installments', 'companyDetail', 'media', 'statusHistories']);
        });
    }
}
