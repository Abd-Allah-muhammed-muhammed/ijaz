<?php

namespace Modules\Guarantor\Actions\Payment;

use Modules\Guarantor\DTOs\ValidateGuarantorPaymentAmountResult;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Models\Payment;
use RuntimeException;

class ValidateGuarantorPaymentAmountAction
{
    public function handle(Payment $payment): ValidateGuarantorPaymentAmountResult
    {
        $paidAmount = (float) $payment->amount;

        return match ($payment->product_type) {
            GuarantorRequest::class => $this->validateIndividual($payment->product, $paidAmount),
            GuarantorInstallment::class => $this->validateInstallment($payment->product, $paidAmount),
            default => throw new RuntimeException('Unsupported guarantor product type: '.$payment->product_type),
        };
    }

    private function validateIndividual(GuarantorRequest $request, float $paidAmount): ValidateGuarantorPaymentAmountResult
    {
        $expectedAmount = (float) $request->total;

        return new ValidateGuarantorPaymentAmountResult(
            isValid: abs($expectedAmount - $paidAmount) < 0.01,
            expectedAmount: $expectedAmount,
            paidAmount: $paidAmount,
            productLabel: 'GuarantorRequest#'.$request->getKey(),
        );
    }

    private function validateInstallment(GuarantorInstallment $installment, float $paidAmount): ValidateGuarantorPaymentAmountResult
    {
        $expectedAmount = (float) $installment->amount;

        return new ValidateGuarantorPaymentAmountResult(
            isValid: abs($expectedAmount - $paidAmount) < 0.01,
            expectedAmount: $expectedAmount,
            paidAmount: $paidAmount,
            productLabel: 'GuarantorInstallment#'.$installment->getKey(),
        );
    }
}
