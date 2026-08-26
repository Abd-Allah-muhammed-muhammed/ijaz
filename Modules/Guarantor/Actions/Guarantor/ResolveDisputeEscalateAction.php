<?php

namespace Modules\Guarantor\Actions\Guarantor;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction as LogGuarantorStatusHistory;
use Modules\Guarantor\Actions\Guarantor\ReverseGuarantorWalletHoldsAction as ReverseGuarantorWalletHolds;
use Modules\Guarantor\Actions\Guarantor\VoidRemainingGuarantorInstallmentsAction as VoidRemainingGuarantorInstallments;
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
        private readonly VoidRemainingGuarantorInstallments $voidRemainingGuarantorInstallmentsAction,
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
            $request = $this->guarantorRepository->findForUpdate($request);

            if ($request->status->isNot(GuarantorStatusEnum::Disputed)) {
                throw new GuarantorException('guarantor.dispute_already_resolved', 422);
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

            $this->voidRemainingGuarantorInstallmentsAction->handle($guarantorRequest->fresh());

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
