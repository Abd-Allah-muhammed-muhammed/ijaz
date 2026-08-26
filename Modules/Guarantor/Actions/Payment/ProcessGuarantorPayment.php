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
        private readonly RejectGuarantorStalePaymentCompletionAction $rejectStalePaymentCompletionAction,
    ) {}

    /**
     * Apply domain updates for an accepted guarantor payment.
     *
     * @return bool True when the payment was applied; false when rejected (stale contract state).
     */
    public function handle(Payment $payment): bool
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return false;
        }

        return match ($payment->product_type) {
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

    private function processIndividualPayment(Payment $payment): bool
    {
        /** @var GuarantorRequest $request */
        $request = $this->guarantorRepository->findForUpdate($payment->product);
        $request->loadMissing('counterparty');

        if (! $this->isIndividualPayable($request)) {
            $this->rejectStalePaymentCompletionAction->handle($payment, $request, 'individual');

            return false;
        }

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

        return true;
    }

    private function processInstallmentPayment(Payment $payment): bool
    {
        /** @var GuarantorInstallment $installment */
        $installment = $payment->product;
        $installment->loadMissing('guarantorRequest');

        /** @var GuarantorRequest $request */
        $request = $this->guarantorRepository->findForUpdate($installment->guarantorRequest);
        $installment = $installment->fresh();
        $installment->setRelation('guarantorRequest', $request);

        if (! $this->isCompanyPaymentPayable($request, $installment)) {
            $this->rejectStalePaymentCompletionAction->handle($payment, $request, 'company_installment');

            return false;
        }

        $this->installmentRepository->update($installment, [
            'status' => InstallmentStatusEnum::Paid,
            'paid_at' => now(),
        ]);

        if ($request->status->isIn([
            GuarantorStatusEnum::Accepted,
            GuarantorStatusEnum::Overdue,
        ])) {
            $fromStatus = $request->status->value;

            $updateData = [
                'status' => GuarantorStatusEnum::InProgress,
            ];

            if ($request->status->is(GuarantorStatusEnum::Overdue)) {
                $updateData['overdue_at'] = null;
            }

            $request = $this->guarantorRepository->update($request, $updateData);
            $request->loadMissing('counterparty');

            $this->logStatusHistory->handle(
                request: $request,
                actor: $request->counterparty,
                fromStatus: $fromStatus,
                toStatus: GuarantorStatusEnum::InProgress->value,
                notes: 'Payment accepted by gateway',
            );
        }

        if ($installment->order <= 1) {
            return true;
        }

        $previousInstallment = $this->installmentRepository->findPreviousPaidInstallment(
            $request,
            (int) $installment->order,
        );

        if ($previousInstallment !== null) {
            ReleaseInstallmentJob::dispatch($previousInstallment, 'payment');
        }

        return true;
    }

    private function isIndividualPayable(GuarantorRequest $request): bool
    {
        if ($request->status->is(GuarantorStatusEnum::Disputed)) {
            return false;
        }

        if ($request->status->isTerminal()) {
            return false;
        }

        return $request->status->is(GuarantorStatusEnum::Accepted);
    }

    private function isCompanyPaymentPayable(GuarantorRequest $request, GuarantorInstallment $installment): bool
    {
        if ($request->status->is(GuarantorStatusEnum::Disputed)) {
            return false;
        }

        if ($request->status->isTerminal()) {
            return false;
        }

        if ($request->status->isNotIn([
            GuarantorStatusEnum::Accepted,
            GuarantorStatusEnum::InProgress,
            GuarantorStatusEnum::Overdue,
        ])) {
            return false;
        }

        return $installment->status->is(InstallmentStatusEnum::Pending);
    }
}
