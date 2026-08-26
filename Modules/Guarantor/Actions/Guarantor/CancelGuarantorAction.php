<?php

namespace Modules\Guarantor\Actions\Guarantor;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction as LogGuarantorStatusHistory;
use Modules\Guarantor\Actions\Guarantor\ReverseGuarantorWalletHoldsAction as ReverseGuarantorWalletHolds;
use Modules\Guarantor\Actions\Guarantor\UpdateGuarantorStatusAction as UpdateGuarantorStatus;
use Modules\Guarantor\Actions\Guarantor\VoidRemainingGuarantorInstallmentsAction as VoidRemainingGuarantorInstallments;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\DTOs\UpdateGuarantorStatusData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Support\GuarantorDisputeHistoryReason;
use Throwable;

class CancelGuarantorAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly UpdateGuarantorStatus $updateStatusAction,
        private readonly ReverseGuarantorWalletHolds $reverseGuarantorWalletHoldsAction,
        private readonly VoidRemainingGuarantorInstallments $voidRemainingGuarantorInstallmentsAction,
        private readonly LogGuarantorStatusHistory $logStatusHistory,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        GuarantorRequest $request,
        string $reason,
        ?string $notes,
        Admin $admin,
    ): void {
        DB::transaction(function () use ($request, $reason, $notes, $admin) {
            $request = $this->guarantorRepository->findForUpdate($request);

            if ($request->status->isIn([
                GuarantorStatusEnum::Cancelled,
                GuarantorStatusEnum::CancelledViaDispute,
                GuarantorStatusEnum::Ended,
                GuarantorStatusEnum::EndedViaDispute,
                GuarantorStatusEnum::Escalated,
                GuarantorStatusEnum::Settled,
            ])) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $wasDisputed = $request->status->is(GuarantorStatusEnum::Disputed);

            $this->updateStatusAction->handle(
                $request,
                new UpdateGuarantorStatusData(
                    status: GuarantorStatusEnum::Cancelled,
                    reason: $reason,
                    notes: $notes,
                ),
                $admin,
                'admin'
            );

            $request = $request->fresh();

            if ($wasDisputed) {
                $this->logStatusHistory->handle(
                    $request,
                    $admin,
                    GuarantorStatusEnum::Cancelled->value,
                    GuarantorStatusEnum::Cancelled->value,
                    reason: GuarantorDisputeHistoryReason::ClosedByAdminCancel,
                    notes: $notes,
                );
            }

            $this->reverseGuarantorWalletHoldsAction->handle($request->fresh());
            $this->voidRemainingGuarantorInstallmentsAction->handle($request->fresh());
        });
    }
}
