<?php

namespace Modules\Guarantor\Actions\Guarantor;

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Guarantor\DetermineGuarantorHeldAmountAction as DetermineGuarantorHeldAmount;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction as LogGuarantorStatusHistory;
use Modules\Guarantor\Actions\Guarantor\VoidRemainingGuarantorInstallmentsAction as VoidRemainingGuarantorInstallments;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorDisputeResolutionEnum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorDisputeResolvedNotification;
use Modules\Wallet\Services\WalletService;
use Throwable;

/**
 * Percentage-split dispute resolution.
 *
 * Rounding: requester gross/fee shares are round(..., 2); the counterparty
 * receives the residual gross (heldGross - requesterGrossShare) so the two
 * shares always sum exactly to the held gross. Fee is taken only from the
 * requester's share (proportional); counterparty credit is never fee-reduced.
 */
class ResolveDisputePercentageSplitAction
{
    public function __construct(
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly InstallmentRepositoryInterface $installmentRepository,
        private readonly DetermineGuarantorHeldAmount $determineGuarantorHeldAmountAction,
        private readonly LogGuarantorStatusHistory $logStatusHistory,
        private readonly VoidRemainingGuarantorInstallments $voidRemainingGuarantorInstallmentsAction,
        private readonly WalletService $walletService,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(
        GuarantorRequest $request,
        Admin $admin,
        int $requesterPercentage,
        ?string $notes = null,
    ): GuarantorRequest {
        if ($requesterPercentage < 0 || $requesterPercentage > 100) {
            throw new GuarantorException('guarantor.invalid_requester_percentage', 422);
        }

        return DB::transaction(function () use ($request, $admin, $requesterPercentage, $notes) {
            $request = $this->guarantorRepository->findForUpdate($request);

            if ($request->status->isNot(GuarantorStatusEnum::Disputed)) {
                throw new GuarantorException('guarantor.dispute_already_resolved', 422);
            }

            $fromStatus = $request->status->value;
            $counterpartyPercentage = 100 - $requesterPercentage;
            $held = $this->determineGuarantorHeldAmountAction->handle($request);

            $request->loadMissing(['requester', 'counterparty']);
            $request->requester->wallet()->lockForUpdate()->firstOrCreate();
            $request->counterparty->wallet()->lockForUpdate()->firstOrCreate();

            /*
             * Requester share rounded to 2dp; counterparty gets the residual so
             * shares always sum to held gross (absorbs any fractional-cent remainder).
             */
            $requesterGrossShare = round($held->gross * $requesterPercentage / 100, 2);
            $counterpartyGrossShare = round($held->gross - $requesterGrossShare, 2);
            $requesterFeeShare = round($held->fee * $requesterPercentage / 100, 2);
            $requesterNetShare = round($requesterGrossShare - $requesterFeeShare, 2);
            $remainderGross = $counterpartyGrossShare;

            if ($requesterGrossShare > 0) {
                $this->walletService->releasePendingCreditToBalance(
                    $request->requester,
                    $requesterGrossShare,
                    $requesterNetShare,
                    $held->operation,
                    "Guarantor#{$request->id} dispute split — requester {$requesterPercentage}%",
                );
            }

            if ($remainderGross > 0) {
                $this->walletService->reversePendingCredit(
                    $request->requester,
                    $remainderGross,
                    $held->operation,
                    "Guarantor#{$request->id} dispute split — voided {$counterpartyPercentage}%",
                );
            }

            if ($counterpartyGrossShare > 0) {
                $this->walletService->credit(
                    $request->counterparty,
                    $counterpartyGrossShare,
                    $held->operation,
                    "Guarantor#{$request->id} dispute split — counterparty {$counterpartyPercentage}%",
                );
            }

            if ($held->gross > 0) {
                $this->walletService->reversePendingDebit(
                    $request->counterparty,
                    $held->gross,
                    $held->operation,
                    "Guarantor#{$request->id} dispute split — debit hold cleared",
                );
            }

            if ($held->installment !== null) {
                $this->installmentRepository->update($held->installment, [
                    'status' => InstallmentStatusEnum::Released,
                    'released_at' => now(),
                ]);
            }

            $this->voidRemainingGuarantorInstallmentsAction->handle($request->fresh());

            $resolution = GuarantorDisputeResolutionEnum::PercentageSplit;
            $guarantorRequest = $this->guarantorRepository->update($request, [
                'status' => GuarantorStatusEnum::Settled,
            ]);

            $historyReason = sprintf(
                '%s:%d/%d',
                $resolution->historyReason(),
                $requesterPercentage,
                $counterpartyPercentage,
            );

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $admin,
                $fromStatus,
                GuarantorStatusEnum::Settled->value,
                reason: $historyReason,
                notes: $notes,
            );

            $guarantorRequest->loadMissing(['requester', 'counterparty']);
            $notification = new GuarantorDisputeResolvedNotification(
                $guarantorRequest,
                $resolution,
                requesterPercentage: $requesterPercentage,
                counterpartyPercentage: $counterpartyPercentage,
                requesterAmount: $requesterNetShare,
                counterpartyAmount: $counterpartyGrossShare,
            );
            $guarantorRequest->requester?->notify($notification);
            $guarantorRequest->counterparty?->notify($notification);

            return $guarantorRequest->load(['requester', 'counterparty', 'installments', 'companyDetail', 'media', 'statusHistories']);
        });
    }
}
