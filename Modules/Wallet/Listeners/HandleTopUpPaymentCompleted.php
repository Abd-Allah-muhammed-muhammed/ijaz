<?php

namespace Modules\Wallet\Listeners;

use App\Enums\OperationStatusEnum;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Wallet\Contracts\Repositories\TopUpRequestRepositoryInterface;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Services\WalletService;

class HandleTopUpPaymentCompleted
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly TopUpRequestRepositoryInterface $topUpRequestRepository,
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;

        if ($payment->product_type !== TopUpRequest::class) {
            return;
        }

        DB::transaction(function () use ($payment) {
            /** @var TopUpRequest $topUp */
            $topUp = $this->topUpRequestRepository->lockForUpdate($payment->product);

            // Idempotent under duplicate PaymentCompleted deliveries.
            if ($topUp->status === OperationStatusEnum::Approved) {
                return;
            }

            $topUp = $this->topUpRequestRepository->update($topUp, [
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
