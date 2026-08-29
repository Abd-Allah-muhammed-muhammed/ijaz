<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction as LogGuarantorStatusHistory;
use Modules\Guarantor\Actions\Guarantor\NotifyGuarantorPartiesAction as NotifyGuarantorParties;
use Modules\Guarantor\Actions\Guarantor\ReleaseGuarantorWalletHoldsAction as ReleaseGuarantorWalletHolds;
use Modules\Guarantor\Actions\Guarantor\VoidRemainingGuarantorInstallmentsAction as VoidRemainingGuarantorInstallments;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorEndRequestedNotification;
use Throwable;

class EndGuarantorAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly LogGuarantorStatusHistory $logStatusHistory,
        private readonly ReleaseGuarantorWalletHolds $releaseGuarantorWalletHoldsAction,
        private readonly VoidRemainingGuarantorInstallments $voidRemainingGuarantorInstallmentsAction,
        private readonly NotifyGuarantorParties $notifyGuarantorPartiesAction,
    ) {}

    /**
     * Counterparty: complete end immediately (unchanged).
     * Requester: request end approval — no wallet release yet.
     *
     * @throws Throwable
     */
    public function handle(GuarantorRequest $request, Model $actor, string $actorRole): GuarantorRequest
    {
        if ($actorRole === 'requester') {
            return $this->requestEndApproval($request, $actor, $actorRole);
        }

        return $this->completeEnd($request, $actor, $actorRole);
    }

    /**
     * Completes an end: wallet release + Ended. Used by counterparty `/end`
     * and by counterparty approve of a pending end request.
     *
     * @throws Throwable
     */
    public function completeEnd(GuarantorRequest $request, Model $actor, string $actorRole): GuarantorRequest
    {
        return DB::transaction(function () use ($request, $actor, $actorRole) {
            $request = $this->guarantorRepository->findForUpdate($request);

            if ($request->status->isIn([
                GuarantorStatusEnum::Cancelled,
                GuarantorStatusEnum::CancelledViaDispute,
                GuarantorStatusEnum::Ended,
                GuarantorStatusEnum::EndedViaDispute,
                GuarantorStatusEnum::Escalated,
                GuarantorStatusEnum::Settled,
                GuarantorStatusEnum::Withdrawn,
            ])) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            if (! GuarantorStatusEnum::isAllowed($request->status, GuarantorStatusEnum::Ended, $actorRole)) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $fromStatus = $request->status->value;

            $guarantorRequest = $this->guarantorRepository->update($request, [
                'status' => GuarantorStatusEnum::Ended,
                'ended_at' => now(),
            ]);

            $this->releaseGuarantorWalletHoldsAction->handle($guarantorRequest->fresh());
            $this->voidRemainingGuarantorInstallmentsAction->handle($guarantorRequest->fresh());

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $actor,
                $fromStatus,
                GuarantorStatusEnum::Ended->value,
                reason: "{$actorRole} ended the guarantor request",
            );

            $this->notifyGuarantorPartiesAction->handle($guarantorRequest);

            return $guarantorRequest->load(['requester', 'counterparty', 'installments', 'companyDetail', 'media']);
        });
    }

    /**
     * @throws Throwable
     */
    private function requestEndApproval(GuarantorRequest $request, Model $actor, string $actorRole): GuarantorRequest
    {
        return DB::transaction(function () use ($request, $actor, $actorRole) {
            $request = $this->guarantorRepository->findForUpdate($request);

            if (! GuarantorStatusEnum::isAllowed(
                $request->status,
                GuarantorStatusEnum::PendingCounterpartyEndApproval,
                $actorRole,
            )) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $fromStatus = $request->status->value;

            $guarantorRequest = $this->guarantorRepository->update($request, [
                'status' => GuarantorStatusEnum::PendingCounterpartyEndApproval,
            ]);

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $actor,
                $fromStatus,
                GuarantorStatusEnum::PendingCounterpartyEndApproval->value,
                reason: 'requester requested end — awaiting counterparty approval',
            );

            $guarantorRequest->loadMissing(['requester', 'counterparty']);
            $guarantorRequest->counterparty?->notify(
                new GuarantorEndRequestedNotification($guarantorRequest)
            );

            return $guarantorRequest->load([
                'requester',
                'counterparty',
                'installments',
                'companyDetail',
                'media',
                'statusHistories',
            ]);
        });
    }
}
