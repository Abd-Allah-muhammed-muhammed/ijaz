<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction as LogGuarantorStatusHistory;
use Modules\Guarantor\Actions\Guarantor\NotifyAdminsOfGuarantorDisputedAction as NotifyAdminsOfGuarantorDisputed;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorDisputedNotification;
use Throwable;

class OpenGuarantorDisputeAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly LogGuarantorStatusHistory $logStatusHistory,
        private readonly NotifyAdminsOfGuarantorDisputed $notifyAdminsOfGuarantorDisputedAction,
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

            if (! GuarantorStatusEnum::isAllowed($request->status, GuarantorStatusEnum::Disputed, $actorRole)) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $fromStatus = $request->status->value;

            $guarantorRequest = $this->guarantorRepository->update($request, [
                'status' => GuarantorStatusEnum::Disputed,
            ]);

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $actor,
                $fromStatus,
                GuarantorStatusEnum::Disputed->value,
                reason: $reason,
            );

            $guarantorRequest->loadMissing(['requester', 'counterparty']);

            $otherParty = $this->otherParty($guarantorRequest, $actor);
            $otherParty?->notify(new GuarantorDisputedNotification($guarantorRequest, $reason));

            $this->notifyAdminsOfGuarantorDisputedAction->handle($guarantorRequest, $reason);

            return $guarantorRequest->load(['requester', 'counterparty', 'installments', 'companyDetail', 'media', 'statusHistories']);
        });
    }

    private function otherParty(GuarantorRequest $request, Model $actor): ?Model
    {
        if (
            $request->requester_type === $actor::class
            && (string) $request->requester_id === (string) $actor->getKey()
        ) {
            return $request->counterparty;
        }

        if (
            $request->counterparty_type === $actor::class
            && (string) $request->counterparty_id === (string) $actor->getKey()
        ) {
            return $request->requester;
        }

        return null;
    }
}
