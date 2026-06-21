<?php

namespace Modules\Payment\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Models\Payment;
use Modules\Payment\Registry\PaymentHandlerRegistry;
use Modules\Payment\Services\PaymentService;

class HandleCallbackAction
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PaymentHandlerRegistry $registry,
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

            $payment->refresh();

            if (! $this->registry->hasHandler($payment->product_type)) {
                return;
            }

            $handler = $this->registry->getHandler($payment->product_type);

            if ($result->isAccepted()) {
                $handler->onSuccess($payment);
            } else {
                $handler->onFailure($payment);
            }
        });

        DB::afterCommit(function () use ($payment, $result) {
            if ($result->isAccepted()) {
                event(new PaymentCompleted($payment->fresh()));
            } else {
                event(new PaymentFailed($payment->fresh()));
            }
        });
    }
}
