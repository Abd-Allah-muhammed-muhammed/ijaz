<?php

namespace Modules\Wallet\Listeners;

use App\Enums\OperationStatusEnum;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentFailed;
use Modules\Wallet\Contracts\Repositories\TopUpRequestRepositoryInterface;
use Modules\Wallet\Models\TopUpRequest;

class HandleTopUpPaymentFailed
{
    public function __construct(
        private readonly TopUpRequestRepositoryInterface $topUpRequestRepository,
    ) {}

    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;

        if ($payment->product_type !== TopUpRequest::class) {
            return;
        }

        DB::transaction(function () use ($payment): void {
            /** @var TopUpRequest $topUp */
            $topUp = $this->topUpRequestRepository->lockForUpdate($payment->product);

            // Already credited — do not roll back via a late failure event.
            if ($topUp->status === OperationStatusEnum::Approved) {
                return;
            }

            $this->topUpRequestRepository->update($topUp, [
                'status' => OperationStatusEnum::Rejected,
                'payment_status' => PaymentStatusEnum::Rejected,
                'transaction_id' => $payment->transaction_id,
                'payment_driver' => $payment->driver,
            ]);
        });
    }
}
