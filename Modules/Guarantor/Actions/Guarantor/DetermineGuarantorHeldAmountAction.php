<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\DTOs\GuarantorHeldAmountData;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Wallet\Services\WalletService;

/**
 * Determines THIS guarantor's currently-held gross/fee/net —
 * Individual: full contract hold (amount+fees / amount);
 * Company: the latest Paid (unreleased) installment only, with
 * proportional fee matching {@see ReleaseInstallmentAction}.
 */
class DetermineGuarantorHeldAmountAction
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function hasUnreleasedHold(GuarantorRequest $request): bool
    {
        $request->loadMissing(['installments', 'requester', 'counterparty']);

        if ($request->type === GuarantorTypeEnum::Company) {
            $hasPaidInstallment = $request->installments
                ->contains(fn (GuarantorInstallment $installment): bool => $installment->status->is(InstallmentStatusEnum::Paid));

            if ($hasPaidInstallment) {
                return true;
            }
        } elseif ($request->status->isIn([
            GuarantorStatusEnum::InProgress,
            GuarantorStatusEnum::Overdue,
            GuarantorStatusEnum::Disputed,
        ])) {
            return true;
        }

        $operations = [
            $request,
            ...$request->installments->all(),
        ];

        $requesterHeld = $this->walletService->sumPendingDeltasForOperations(
            $request->requester,
            $operations,
        );
        $counterpartyHeld = $this->walletService->sumPendingDeltasForOperations(
            $request->counterparty,
            $operations,
        );

        return $requesterHeld['pending_credit'] > 0
            || $counterpartyHeld['pending_debit'] > 0;
    }

    public function handle(GuarantorRequest $request): GuarantorHeldAmountData
    {
        $request->loadMissing(['installments']);

        if ($request->type === GuarantorTypeEnum::Individual) {
            $gross = (float) $request->amount + (float) $request->fees;
            $fee = (float) $request->fees;
            $net = (float) $request->amount;

            return new GuarantorHeldAmountData(
                gross: $gross,
                fee: $fee,
                net: $net,
                operation: $request,
            );
        }

        $installment = $request->installments
            ->where('status', InstallmentStatusEnum::Paid)
            ->sortByDesc('order')
            ->first();

        if ($installment === null) {
            throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
        }

        $gross = (float) $installment->amount;
        $fee = (float) $request->amount > 0
            ? round($gross / (float) $request->amount * (float) $request->fees, 2)
            : 0.0;
        $net = round($gross - $fee, 2);

        return new GuarantorHeldAmountData(
            gross: $gross,
            fee: $fee,
            net: $net,
            operation: $installment,
            installment: $installment,
        );
    }
}
