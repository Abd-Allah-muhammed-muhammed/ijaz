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

class AddCounterpartyWalletTransaction
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function __invoke(Payment $payment, Closure $next): mixed
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return $next($payment);
        }

        [$recipient, , $operation] = $this->resolveRecipient($payment);

        $this->walletService->addPendingCredit(
            $recipient,
            (float) $payment->amount,
            $operation,
            'Guarantor payment received — pending release',
        );

        return $next($payment);
    }

    /**
     * @return array{0: Model, 1: float, 2: Model}
     */
    private function resolveRecipient(Payment $payment): array
    {
        return match ($payment->product_type) {
            GuarantorRequest::class => $this->fromGuarantorRequest($payment->product),
            GuarantorInstallment::class => $this->fromInstallment($payment->product),
            default => throw new RuntimeException('Unsupported guarantor product type: '.$payment->product_type),
        };
    }

    /**
     * @return array{0: Model, 1: float, 2: GuarantorRequest}
     */
    private function fromGuarantorRequest(GuarantorRequest $request): array
    {
        $request->loadMissing('requester');

        return [$request->requester, (float) $request->fees, $request];
    }

    /**
     * @return array{0: Model, 1: float, 2: GuarantorInstallment}
     */
    private function fromInstallment(GuarantorInstallment $installment): array
    {
        $installment->loadMissing('guarantorRequest.requester');
        $request = $installment->guarantorRequest;
        $fees = (float) $request->amount > 0
            ? round((float) $installment->amount / (float) $request->amount * (float) $request->fees, 2)
            : 0.0;

        return [$request->requester, $fees, $installment];
    }
}
