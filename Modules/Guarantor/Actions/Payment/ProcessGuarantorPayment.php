<?php

namespace Modules\Guarantor\Actions\Payment;

use Closure;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Jobs\ReleaseInstallmentJob;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use RuntimeException;

class ProcessGuarantorPayment
{
    public function __construct(
        private readonly LogGuarantorStatusHistoryAction $logStatusHistory,
    ) {}

    public function handle(Payment $payment): void
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return;
        }

        match ($payment->product_type) {
            GuarantorRequest::class => $this->processIndividualPayment($payment),
            GuarantorInstallment::class => $this->processInstallmentPayment($payment),
            default => throw new RuntimeException('Unsupported guarantor product type: '.$payment->product_type),
        };
    }

    public function __invoke(Payment $payment, Closure $next): mixed
    {
        $this->handle($payment);

        return $next($payment);
    }

    private function processIndividualPayment(Payment $payment): void
    {
        /** @var GuarantorRequest $request */
        $request = $payment->product;
        $request->loadMissing('counterparty');

        $request->update(['status' => GuarantorStatusEnum::InProgress]);

        $this->logStatusHistory->handle(
            request: $request,
            actor: $request->counterparty,
            fromStatus: GuarantorStatusEnum::Accepted->value,
            toStatus: GuarantorStatusEnum::InProgress->value,
            notes: 'Payment accepted by gateway',
        );
    }

    private function processInstallmentPayment(Payment $payment): void
    {
        /** @var GuarantorInstallment $installment */
        $installment = $payment->product;
        $installment->loadMissing('guarantorRequest');

        $installment->update([
            'status' => InstallmentStatusEnum::Paid,
            'paid_at' => now(),
        ]);

        /** @var GuarantorRequest $request */
        $request = $installment->guarantorRequest;

        if ($request->status->is(GuarantorStatusEnum::Overdue)) {
            $request->update([
                'status' => GuarantorStatusEnum::InProgress,
                'overdue_at' => null,
            ]);
        }

        if ($installment->order <= 1) {
            return;
        }

        $previousInstallment = $request->installments()
            ->where('order', $installment->order - 1)
            ->where('status', InstallmentStatusEnum::Paid)
            ->first();

        if ($previousInstallment !== null) {
            ReleaseInstallmentJob::dispatch($previousInstallment, 'payment');
        }
    }
}
