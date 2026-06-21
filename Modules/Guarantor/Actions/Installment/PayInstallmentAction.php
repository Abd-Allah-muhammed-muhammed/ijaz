<?php

namespace Modules\Guarantor\Actions\Installment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Services\PaymentService;
use Throwable;

class PayInstallmentAction
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws Throwable
     */
    public function handle(GuarantorRequest $request, GuarantorInstallment $installment, Model $actor): array
    {
        return DB::transaction(function () use ($request, $installment, $actor) {
            if (! in_array($request->status, [
                GuarantorStatusEnum::Accepted,
                GuarantorStatusEnum::InProgress,
                GuarantorStatusEnum::Overdue,
            ], true)) {
                throw new GuarantorException('guarantor.status_transition_not_allowed', 422);
            }

            if ($installment->status->isNot(InstallmentStatusEnum::Pending)) {
                throw new GuarantorException('guarantor.already_paid', 422);
            }

            if ($installment->order > 1) {
                $previous = $request->installments()
                    ->where('order', $installment->order - 1)
                    ->first();

                if ($previous === null || ! in_array($previous->status, [
                    InstallmentStatusEnum::Paid,
                    InstallmentStatusEnum::Released,
                ], true)) {
                    throw new GuarantorException('guarantor.previous_installment_not_paid', 422);
                }
            }

            $result = $this->paymentService->initiate(
                owner: $actor,
                product: $installment,
                amount: $installment->amount,
            );

            return $result->toArray();
        });
    }
}
