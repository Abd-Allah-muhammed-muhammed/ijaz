<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorEndedNotification;
use Modules\Wallet\Services\WalletService;
use Throwable;

class CancelGuarantorAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly LogGuarantorStatusHistoryAction $logStatusHistory,
        private readonly WalletService $walletService,
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

            $guarantorRequest->load(['requester', 'counterparty']);

            collect([$guarantorRequest->requester, $guarantorRequest->counterparty])
                ->each->notify(new GuarantorEndedNotification($guarantorRequest));
        });
    }

    private function reverseWalletHolds(GuarantorRequest $request): void
    {
        $request->loadMissing(['requester', 'counterparty']);

        $total = (float) $request->amount + (float) $request->fees;

        $counterpartyWallet = $request->counterparty->wallet()->lockForUpdate()->firstOrCreate();
        if ((float) $counterpartyWallet->pending_debit >= $total) {
            $this->walletService->reversePendingDebit(
                $request->counterparty,
                $total,
                $request,
                "Guarantor#{$request->id} cancelled",
            );
        }

        $requesterWallet = $request->requester->wallet()->lockForUpdate()->firstOrCreate();
        if ((float) $requesterWallet->pending_credit >= $total) {
            $this->walletService->reversePendingCredit(
                $request->requester,
                $total,
                $request,
                "Guarantor#{$request->id} cancelled",
            );
        }
    }
}
