<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorEndApprovedNotification;
use Throwable;

class ApproveEndRequestAction
{
    public function __construct(
        private readonly EndGuarantorAction $endGuarantorAction,
    ) {}

    /**
     * Counterparty approves a pending end request — same wallet release as ordinary End.
     *
     * @throws Throwable
     */
    public function handle(GuarantorRequest $request, Model $actor, string $actorRole): GuarantorRequest
    {
        if ($actorRole !== 'counterparty') {
            throw new GuarantorException('guarantor.unauthorized', 403);
        }

        if ($request->status->isNot(GuarantorStatusEnum::PendingCounterpartyEndApproval)) {
            throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
        }

        $guarantorRequest = $this->endGuarantorAction->completeEnd($request, $actor, $actorRole);

        $guarantorRequest->loadMissing('requester');
        $guarantorRequest->requester?->notify(
            new GuarantorEndApprovedNotification($guarantorRequest)
        );

        return $guarantorRequest;
    }
}
