<?php

namespace Modules\Payment\Handlers;

use App\Enums\OperationStatusEnum;
use Modules\Payment\Contracts\PaymentHandlerInterface;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Services\WalletService;

class TopUpPaymentHandler implements PaymentHandlerInterface
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function onSuccess(Payment $payment): void
    {
        /** @var TopUpRequest $topUp */
        $topUp = $payment->product;

        $topUp->update([
            'status' => OperationStatusEnum::Approved,
            'payment_status' => PaymentStatusEnum::Accepted,
            'transaction_id' => $payment->transaction_id,
            'payment_driver' => $payment->driver,
        ]);

        $this->walletService->credit(
            owner: $payment->user,
            amount: $payment->amount,
            operation: $topUp,
            description: "Online top-up approved — TopUpRequest#{$topUp->id}",
        );
    }

    public function onFailure(Payment $payment): void
    {
        /** @var TopUpRequest $topUp */
        $topUp = $payment->product;

        $topUp->update([
            'payment_status' => PaymentStatusEnum::Rejected,
        ]);
    }

    public function productTypes(): array
    {
        return [TopUpRequest::class];
    }
}
