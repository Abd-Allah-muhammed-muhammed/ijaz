<?php

namespace Modules\Payment\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\PaymentService;

class HandleCallbackAction
{
    public function __construct(
        private readonly PaymentService $paymentService,
    ) {}

    public function handle(Payment $payment, array $payload): void
    {
        if ($payment->status !== PaymentStatusEnum::Pending) {
            return;
        }

        $gateway = $this->paymentService->resolveGateway($payment->driver);
        $result = $gateway->verify($payment, $payload);

        DB::transaction(function () use ($payment, $result) {
            $payment->update([
                'status' => $result->status,
                'transaction_id' => $result->transactionId,
                'response' => $result->rawResponse,
                'message' => $result->message,
            ]);
        });

        DB::afterCommit(function () use ($payment, $result) {
            $payment->refresh();

            if ($result->isAccepted()) {
                event(new PaymentCompleted($payment));
            } else {
                event(new PaymentFailed($payment));
            }
        });
    }
}
