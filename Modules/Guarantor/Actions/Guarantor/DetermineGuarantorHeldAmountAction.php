<?php

namespace Modules\Guarantor\Actions\Guarantor;

use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\DTOs\GuarantorHeldAmountData;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorRequest;

/**
 * Determines THIS guarantor's currently-held gross/fee/net —
 * Individual: full contract hold (amount+fees / amount);
 * Company: the latest Paid (unreleased) installment only, with
 * proportional fee matching {@see ReleaseInstallmentAction}.
 */
class DetermineGuarantorHeldAmountAction
{
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
