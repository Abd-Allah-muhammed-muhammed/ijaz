<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Throwable;

class CancelGuarantorAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly LogGuarantorStatusHistoryAction $logStatusHistory,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(GuarantorRequest $request, string $reason, Model $actor, string $actorRole): void
    {
        DB::transaction(function () use ($request, $reason, $actor, $actorRole) {
            if (! GuarantorStatusEnum::isAllowed($request->status, GuarantorStatusEnum::Cancelled, $actorRole)) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            $fromStatus = $request->status->value;
            $hadPayment = in_array($request->status, [
                GuarantorStatusEnum::InProgress,
                GuarantorStatusEnum::Overdue,
            ], true);

            $guarantorRequest = $this->guarantorRepository->update($request, [
                'status' => GuarantorStatusEnum::Cancelled,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            if ($hadPayment) {
                $this->reverseWalletHolds($guarantorRequest);
            }

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $actor,
                $fromStatus,
                GuarantorStatusEnum::Cancelled->value,
                $reason,
            );

            // TODO: notify both parties (Phase 13)
        });
    }

    private function reverseWalletHolds(GuarantorRequest $request): void
    {
        $request->loadMissing(['requester', 'counterparty']);

        $total = (float) $request->amount + (float) $request->fees;

        $counterpartyWallet = $request->counterparty->wallet()->lockForUpdate()->firstOrCreate();
        $requesterWallet = $request->requester->wallet()->lockForUpdate()->firstOrCreate();

        if ((float) $counterpartyWallet->pending_debit >= $total) {
            $counterpartyWallet->decrement('pending_debit', $total);
        }

        if ((float) $requesterWallet->pending_credit >= $total) {
            $requesterWallet->decrement('pending_credit', $total);
        }
    }
}
