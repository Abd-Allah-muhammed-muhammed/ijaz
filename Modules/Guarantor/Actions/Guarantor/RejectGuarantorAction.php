<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorCounterpartyRejectedNotification;
use Throwable;

class RejectGuarantorAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly LogGuarantorStatusHistoryAction $logStatusHistory,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        GuarantorRequest $request,
        Model $actor,
        string $actorRole,
        string $reason,
    ): GuarantorRequest {
        return DB::transaction(function () use ($request, $actor, $actorRole, $reason) {
            $request = $this->guarantorRepository->findForUpdate($request);

            if (! GuarantorStatusEnum::isAllowed(
                $request->status,
                GuarantorStatusEnum::Rejected,
                $actorRole,
            )) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $fromStatus = $request->status->value;

            $guarantorRequest = $this->guarantorRepository->update($request, [
                'status' => GuarantorStatusEnum::Rejected,
                'rejected_at' => now(),
            ]);

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $actor,
                $fromStatus,
                GuarantorStatusEnum::Rejected->value,
                reason: $reason,
            );

            $guarantorRequest->loadMissing('requester');
            $guarantorRequest->requester->notify(
                new GuarantorCounterpartyRejectedNotification($guarantorRequest)
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
