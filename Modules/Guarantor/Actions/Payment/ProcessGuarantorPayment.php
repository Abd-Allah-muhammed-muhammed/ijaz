<?php

namespace Modules\Guarantor\Actions\Payment;

use Closure;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
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
        private readonly GuarantorRepositoryInterface $guarantorRepository,
        private readonly InstallmentRepositoryInterface $installmentRepository,
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

        $this->guarantorRepository->update($request, [
            'status' => GuarantorStatusEnum::InProgress,
        ]);

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

        $this->installmentRepository->update($installment, [
            'status' => InstallmentStatusEnum::Paid,
            'paid_at' => now(),
        ]);

        /** @var GuarantorRequest $request */
        $request = $installment->guarantorRequest;

        if ($request->status->is(GuarantorStatusEnum::Overdue)) {
            $this->guarantorRepository->update($request, [
                'status' => GuarantorStatusEnum::InProgress,
                'overdue_at' => null,
            ]);
        }

        if ($installment->order <= 1) {
            return;
        }

        $previousInstallment = $this->installmentRepository->findPreviousPaidInstallment(
            $request,
            (int) $installment->order,
        );

        if ($previousInstallment !== null) {
            ReleaseInstallmentJob::dispatch($previousInstallment, 'payment');
        }
    }
}
