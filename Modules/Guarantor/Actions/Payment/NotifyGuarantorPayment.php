<?php

namespace Modules\Guarantor\Actions\Payment;

use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorPaymentReceivedNotification;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;

class NotifyGuarantorPayment
{
    public function handle(GuarantorRequest $request, Payment $payment, ?GuarantorInstallment $installment = null): void
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return;
        }

        $request->loadMissing(['requester', 'counterparty']);

        $notification = new GuarantorPaymentReceivedNotification($request, $payment, $installment);

        $request->requester?->notify($notification);
        $request->counterparty?->notify($notification);
    }
}
