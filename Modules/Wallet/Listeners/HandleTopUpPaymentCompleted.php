<?php

namespace Modules\Wallet\Listeners;

use App\Enums\OperationStatusEnum;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Services\WalletService;

class HandleTopUpPaymentCompleted
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;

        if ($payment->product_type !== TopUpRequest::class) {
            return;
        }

        DB::transaction(function () use ($payment) {
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
        });
    }
}
