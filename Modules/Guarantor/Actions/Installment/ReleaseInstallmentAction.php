<?php

namespace Modules\Guarantor\Actions\Installment;

use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\InstallmentReleasedNotification;
use Modules\Wallet\Services\WalletService;
use Throwable;

class ReleaseInstallmentAction
{
    public function __construct(
        private readonly InstallmentRepositoryInterface $installmentRepository,
        private readonly LogGuarantorStatusHistoryAction $logStatusHistory,
        private readonly WalletService $walletService,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(GuarantorInstallment $installment, string $trigger = 'payment'): void
    {
        DB::transaction(function () use ($installment, $trigger) {
            $installment->loadMissing('guarantorRequest.requester');

            /** @var GuarantorRequest $guarantorRequest */
            $guarantorRequest = $installment->guarantorRequest;

            if ($guarantorRequest->status->is(GuarantorStatusEnum::Disputed)) {
                throw new GuarantorException('guarantor.release_denied_active_dispute', 422);
            }

            if ($trigger === 'admin' && $guarantorRequest->status->isTerminal()) {
                throw new GuarantorException('guarantor.release_denied_guarantor_terminal', 422);
            }

            if ($installment->status->is(InstallmentStatusEnum::Reversed)) {
                throw new GuarantorException('guarantor.release_denied_installment_reversed', 422);
            }

            if ($installment->status->is(InstallmentStatusEnum::Released)) {
                throw new GuarantorException('guarantor.already_paid', 422);
            }

            if ($installment->status->isNot(InstallmentStatusEnum::Paid)) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }
            $requester = $guarantorRequest->requester;

            $feesPortion = (float) $guarantorRequest->amount > 0
                ? round((float) $installment->amount / (float) $guarantorRequest->amount * (float) $guarantorRequest->fees, 2)
                : 0.0;
            $releaseAmount = (float) $installment->amount - $feesPortion;

            $this->walletService->releasePendingCreditToBalance(
                $requester,
                (float) $installment->amount,
                $releaseAmount,
                $installment,
                "Installment {$installment->order} released via {$trigger}",
            );

            $this->installmentRepository->update($installment, [
                'status' => InstallmentStatusEnum::Released,
                'released_at' => now(),
            ]);

            $this->logStatusHistory->handle(
                $guarantorRequest,
                $requester,
                $guarantorRequest->status->value,
                $guarantorRequest->status->value,
                notes: "Installment {$installment->order} released via {$trigger}",
            );

            $this->installmentRepository->refresh($installment);

            $requester->notify(new InstallmentReleasedNotification($installment));
        });
    }
}
