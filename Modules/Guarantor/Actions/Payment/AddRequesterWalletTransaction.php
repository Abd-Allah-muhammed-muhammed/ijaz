<?php

namespace Modules\Guarantor\Actions\Payment;

use App\Enums\Payment\PaymentStatusEnum;
use App\Models\Payment;
use App\Models\Wallet;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use RuntimeException;

class AddRequesterWalletTransaction
{
    public function __invoke(Payment $payment, Closure $next): mixed
    {
        if ($payment->status->isNot(PaymentStatusEnum::Accepted)) {
            return $next($payment);
        }

        [$payer, $operation] = $this->resolvePayer($payment);

        /** @var Wallet $wallet */
        $wallet = $payer->wallet()->lockForUpdate()->firstOrCreate();
        $balanceBefore = (float) $wallet->balance;

        $wallet->increment('pending_debit', $payment->amount);

        $payer->walletTTransactions()->create([
            'wallet_id' => $wallet->id,
            'debit' => 0,
            'credit' => 0,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore,
            'operation_type' => $operation::class,
            'operation_id' => $operation->getKey(),
            'pending_credit' => 0,
            'pending_debit' => $payment->amount,
            'description' => 'Guarantor payment sent — pending release',
        ]);

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
