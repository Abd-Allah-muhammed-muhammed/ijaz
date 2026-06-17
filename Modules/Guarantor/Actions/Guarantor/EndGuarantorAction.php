<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorEndedNotification;
use Throwable;

class EndGuarantorAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly LogGuarantorStatusHistoryAction $logStatusHistory,
        private readonly ReleaseInstallmentAction $releaseInstallmentAction,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(GuarantorRequest $request, Model $actor, string $actorRole): void
    {
        DB::transaction(function () use ($request, $actor, $actorRole) {
            if (! GuarantorStatusEnum::isAllowed($request->status, GuarantorStatusEnum::Ended, $actorRole)) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $fromStatus = $request->status->value;

            $guarantorRequest = $this->guarantorRepository->update($request, [
                'status' => GuarantorStatusEnum::Ended,
                'ended_at' => now(),
            ]);

            if ($guarantorRequest->type === GuarantorTypeEnum::Individual) {
                $this->releaseIndividualWallets($guarantorRequest);
            } else {
                $this->releaseLastPaidInstallment($guarantorRequest);
            }

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $actor,
                $fromStatus,
                GuarantorStatusEnum::Ended->value,
                reason: "{$actorRole} ended the guarantor request",
            );

            $guarantorRequest->load(['requester', 'counterparty']);

            collect([$guarantorRequest->requester, $guarantorRequest->counterparty])
                ->each->notify(new GuarantorEndedNotification($guarantorRequest));
        });
    }

    private function releaseIndividualWallets(GuarantorRequest $request): void
    {
        $request->loadMissing(['requester', 'counterparty']);

        $requesterWallet = $request->requester->wallet()->lockForUpdate()->firstOrCreate();
        $counterpartyWallet = $request->counterparty->wallet()->lockForUpdate()->firstOrCreate();

        $pendingCredit = (float) $requesterWallet->pending_credit;
        if ($pendingCredit > 0) {
            $requesterWallet->decrement('pending_credit', $pendingCredit);
            $requesterWallet->increment('balance', $pendingCredit);
        }

        $pendingDebit = (float) $counterpartyWallet->pending_debit;
        if ($pendingDebit > 0) {
            $counterpartyWallet->decrement('pending_debit', $pendingDebit);
        }
    }

    private function releaseLastPaidInstallment(GuarantorRequest $request): void
    {
        $installment = $request->installments()
            ->where('status', InstallmentStatusEnum::Paid)
            ->orderByDesc('order')
            ->first();

        if ($installment !== null) {
            $this->releaseInstallmentAction->handle($installment, 'end');
        }
    }
}
