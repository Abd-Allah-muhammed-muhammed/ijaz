<?php

namespace Modules\Guarantor\Actions\Guarantor;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction as LogGuarantorStatusHistory;
use Modules\Guarantor\Actions\Guarantor\ReleaseGuarantorWalletHoldsAction as ReleaseGuarantorWalletHolds;
use Modules\Guarantor\Actions\Guarantor\ReverseGuarantorWalletHoldsAction as ReverseGuarantorWalletHolds;
use Modules\Guarantor\Actions\Guarantor\VoidRemainingGuarantorInstallmentsAction as VoidRemainingGuarantorInstallments;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorDisputeResolutionEnum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorDisputeResolvedNotification;
use Throwable;

class ResolveDisputeFullToPartyAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly LogGuarantorStatusHistory $logStatusHistory,
        private readonly ReleaseGuarantorWalletHolds $releaseGuarantorWalletHoldsAction,
        private readonly ReverseGuarantorWalletHolds $reverseGuarantorWalletHoldsAction,
        private readonly VoidRemainingGuarantorInstallments $voidRemainingGuarantorInstallmentsAction,
    ) {}

    /**
     * @param  'requester'|'counterparty'  $favorParty
     *
     * @throws Throwable
     */
    public function handle(
        GuarantorRequest $request,
        Admin $admin,
        string $favorParty,
        ?string $notes = null,
    ): GuarantorRequest {
        if (! in_array($favorParty, ['requester', 'counterparty'], true)) {
            throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
        }

        return DB::transaction(function () use ($request, $admin, $favorParty, $notes) {
            if ($request->status->isNot(GuarantorStatusEnum::Disputed)) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $fromStatus = $request->status->value;
            $resolution = $favorParty === 'requester'
                ? GuarantorDisputeResolutionEnum::FullRequester
                : GuarantorDisputeResolutionEnum::FullCounterparty;

            if ($favorParty === 'requester') {
                $guarantorRequest = $this->guarantorRepository->update($request, [
                    'status' => GuarantorStatusEnum::EndedViaDispute,
                    'ended_at' => now(),
                ]);
                $this->releaseGuarantorWalletHoldsAction->handle($guarantorRequest->fresh());
            } else {
                $guarantorRequest = $this->guarantorRepository->update($request, [
                    'status' => GuarantorStatusEnum::CancelledViaDispute,
                    'cancelled_at' => now(),
                    'cancellation_reason' => $resolution->historyReason(),
                ]);
                $this->reverseGuarantorWalletHoldsAction->handle(
                    $guarantorRequest->fresh(),
                    'dispute resolved — counterparty',
                );
            }

            $this->voidRemainingGuarantorInstallmentsAction->handle($guarantorRequest->fresh());

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $admin,
                $fromStatus,
                $guarantorRequest->status->value,
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
