<?php

namespace Modules\Guarantor\Actions\Payment;

use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Wallet\Services\WalletService;
use RuntimeException;

class AddRequesterWalletTransaction
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function __invoke(Payment $payment, Closure $next): mixed
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return $next($payment);
        }

        [$payer, $operation] = $this->resolvePayer($payment);

        $this->walletService->addPendingDebit(
            $payer,
            (float) $payment->amount,
            $operation,
            'Guarantor payment sent — pending release',
        );

        return $next($payment);
    }

    /**
     * @return array{0: Model, 1: Model}
     */
    private function resolvePayer(Payment $payment): array
    {
        return match ($payment->product_type) {
            GuarantorRequest::class => $this->fromGuarantorRequest($payment->product),
            GuarantorInstallment::class => $this->fromInstallment($payment->product),
            default => throw new RuntimeException('Unsupported guarantor product type: '.$payment->product_type),
        };
    }

    /**
     * @return array{0: Model, 1: GuarantorRequest}
     */
    private function fromGuarantorRequest(GuarantorRequest $request): array
    {
        $request->loadMissing('counterparty');

        return [$request->counterparty, $request];
    }

    /**
     * @return array{0: Model, 1: GuarantorInstallment}
     */
    private function fromInstallment(GuarantorInstallment $installment): array
    {
        $installment->loadMissing('guarantorRequest.counterparty');

        return [$installment->guarantorRequest->counterparty, $installment];
    }
}
