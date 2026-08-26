<?php

namespace Modules\Guarantor\Actions\Payment;

use Illuminate\Support\Facades\Log;
use Modules\Guarantor\DTOs\ValidateGuarantorPaymentAmountResult;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;

class RejectGuarantorPaymentAmountMismatchAction
{
    public function handle(Payment $payment, ValidateGuarantorPaymentAmountResult $validation): void
    {
        $payment->update([
            'status' => PaymentStatusEnum::NeedsReview,
            'message' => sprintf(
                'Guarantor payment amount mismatch: paid %.2f, expected %.2f (%s)',
                $validation->paidAmount,
                $validation->expectedAmount,
                $validation->productLabel,
            ),
        ]);

        Log::warning('Guarantor payment amount mismatch — payment flagged for admin review', [
            'payment_id' => $payment->id,
            'product_type' => $payment->product_type,
            'product_id' => $payment->product_id,
            'paid_amount' => $validation->paidAmount,
            'expected_amount' => $validation->expectedAmount,
            'product_label' => $validation->productLabel,
        ]);
    }
}
