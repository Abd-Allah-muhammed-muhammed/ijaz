<?php

namespace Modules\Guarantor\Actions\Payment;

use Illuminate\Support\Facades\Log;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;

class RejectGuarantorStalePaymentCompletionAction
{
    public function handle(Payment $payment, GuarantorRequest $request, string $context): void
    {
        $payment->update([
            'status' => PaymentStatusEnum::NeedsReview,
            'message' => sprintf(
                'Guarantor payment completion rejected: parent status %s is no longer payable (%s, guarantor %s)',
                $request->status->value,
                $context,
                $request->getKey(),
            ),
        ]);

        Log::warning('Guarantor payment completion rejected — contract closed before gateway callback', [
            'payment_id' => $payment->id,
            'guarantor_request_id' => $request->getKey(),
            'guarantor_status' => $request->status->value,
            'product_type' => $payment->product_type,
            'product_id' => $payment->product_id,
            'context' => $context,
        ]);
    }
}
