<?php

namespace Modules\Wallet\Listeners;

use App\Enums\OperationStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Models\Payment;
use Modules\Wallet\Contracts\Repositories\TopUpRequestRepositoryInterface;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
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

            // Already credited for this top-up — idempotent under duplicate deliveries.
            if (
                $topUp->status === OperationStatusEnum::Approved
                && $topUp->payment_status === PaymentStatusEnum::Accepted
            ) {
                return;
            }

            // Terminal rejection from a failed payment — never revive or credit.
            if ($topUp->status === OperationStatusEnum::Rejected) {
                Log::warning('Top-up payment completed ignored: top-up already rejected', [
                    'payment_id' => $payment->id,
                    'top_up_request_id' => $topUp->id,
                ]);

                return;
            }

            $paidAmount = (float) $payment->amount;
            $expectedAmount = (float) $topUp->amount;

            if (abs($paidAmount - $expectedAmount) >= 0.01) {
                $this->flagAmountMismatch($payment, $topUp, $paidAmount, $expectedAmount);

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
                amount: $expectedAmount,
                operation: $topUp,
                description: WalletTransactionEntryKindEnum::TopupCredited->translationKey(),
                entryKind: WalletTransactionEntryKindEnum::TopupCredited,
            );
        });
    }

    private function flagAmountMismatch(
        Payment $payment,
        TopUpRequest $topUp,
        float $paidAmount,
        float $expectedAmount,
    ): void {
        $payment->update([
            'status' => PaymentStatusEnum::NeedsReview,
            'message' => sprintf(
                'Top-up payment amount mismatch: paid %.2f, expected %.2f',
                $paidAmount,
                $expectedAmount,
            ),
        ]);

        $this->topUpRequestRepository->update($topUp, [
            'payment_status' => PaymentStatusEnum::NeedsReview,
            'transaction_id' => $payment->transaction_id,
            'payment_driver' => $payment->driver,
        ]);

        Log::warning('Top-up payment amount mismatch — payment flagged for admin review', [
            'payment_id' => $payment->id,
            'top_up_request_id' => $topUp->id,
            'paid_amount' => $paidAmount,
            'expected_amount' => $expectedAmount,
        ]);
    }
}
