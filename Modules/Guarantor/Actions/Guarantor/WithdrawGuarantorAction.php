<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction as LogGuarantorStatusHistory;
use Modules\Guarantor\Actions\Guarantor\NotifyAdminsOfGuarantorWithdrawnAction as NotifyAdminsOfGuarantorWithdrawn;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorWithdrawnNotificationAudience;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorWithdrawnNotification;
use Throwable;

class WithdrawGuarantorAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly LogGuarantorStatusHistory $logStatusHistory,
        private readonly NotifyAdminsOfGuarantorWithdrawn $notifyAdminsOfGuarantorWithdrawnAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        GuarantorRequest $request,
        Model $actor,
        string $actorRole,
        ?string $reason = null,
    ): GuarantorRequest {
        return DB::transaction(function () use ($request, $actor, $actorRole, $reason) {
            $request = $this->guarantorRepository->findForUpdate($request);

            $this->assertWithdrawAllowed($request, $actorRole);

            $fromStatus = $request->status->value;

            $guarantorRequest = $this->guarantorRepository->update($request, [
                'status' => GuarantorStatusEnum::Withdrawn,
            ]);

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $actor,
                $fromStatus,
                GuarantorStatusEnum::Withdrawn->value,
                reason: $reason,
            );

            $guarantorRequest->loadMissing(['requester', 'counterparty']);

            $actor->notify(new GuarantorWithdrawnNotification(
                $guarantorRequest,
                GuarantorWithdrawnNotificationAudience::Withdrawer,
                $reason,
            ));

            $otherParty = $this->otherParty($guarantorRequest, $actor);
            $otherParty?->notify(new GuarantorWithdrawnNotification(
                $guarantorRequest,
                GuarantorWithdrawnNotificationAudience::OtherParty,
                $reason,
            ));

            $this->notifyAdminsOfGuarantorWithdrawnAction->handle($guarantorRequest, $reason);

            return $guarantorRequest->load(['requester', 'counterparty', 'installments', 'companyDetail', 'media', 'statusHistories']);
        });
    }

    private function assertWithdrawAllowed(GuarantorRequest $request, string $actorRole): void
    {
        if ($request->status->is(GuarantorStatusEnum::ApprovedByAdmin)) {
            if ($actorRole !== 'requester') {
                throw new GuarantorException('guarantor.withdraw_not_allowed', 422);
            }

            return;
        }

        if ($request->status->is(GuarantorStatusEnum::Accepted)) {
            return;
        }

        throw new GuarantorException('guarantor.withdraw_not_allowed', 422);
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
