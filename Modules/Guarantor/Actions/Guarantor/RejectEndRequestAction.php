<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\StatusHistoryRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorEndRejectedNotification;
use Throwable;

class RejectEndRequestAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly StatusHistoryRepositoryInterface $statusHistory,
        private readonly LogGuarantorStatusHistoryAction $logStatusHistory,
    ) {}

    /**
     * Counterparty rejects a pending end request — reverts to the prior status
     * from status_histories.from_status. No wallet change.
     *
     * @throws Throwable
     */
    public function handle(
        GuarantorRequest $request,
        Model $actor,
        string $actorRole,
        string $reason,
    ): GuarantorRequest {
        return DB::transaction(function () use ($request, $actor, $actorRole, $reason) {
            if ($actorRole !== 'counterparty') {
                throw new GuarantorException('guarantor.unauthorized', 403);
            }

            $request = $this->guarantorRepository->findForUpdate($request);

            if ($request->status->isNot(GuarantorStatusEnum::PendingCounterpartyEndApproval)) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $history = $this->statusHistory->findLatestTransitionTo(
                $request,
                GuarantorStatusEnum::PendingCounterpartyEndApproval->value,
            );

            if ($history === null || $history->from_status === null) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $priorStatus = GuarantorStatusEnum::tryFrom($history->from_status);

            if ($priorStatus === null) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            if (! GuarantorStatusEnum::isAllowed(
                $request->status,
                $priorStatus,
                $actorRole,
            )) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $fromStatus = $request->status->value;

            $guarantorRequest = $this->guarantorRepository->update($request, [
                'status' => $priorStatus,
            ]);

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $actor,
                $fromStatus,
                $priorStatus->value,
                reason: $reason,
            );

            $guarantorRequest->loadMissing('requester');
            $guarantorRequest->requester?->notify(
                new GuarantorEndRejectedNotification($guarantorRequest, $reason)
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
