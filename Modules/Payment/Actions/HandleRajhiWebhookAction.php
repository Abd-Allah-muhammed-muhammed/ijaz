<?php

namespace Modules\Payment\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Models\Payment;
use RuntimeException;

class HandleRajhiWebhookAction
{
    public function __construct(
        private readonly HandleRajhiCallbackAction $callbackAction,
    ) {}

    public function handle(array $payload): void
    {
        // Resolve payment by trackId
        $trackId = $payload['trackId'] ?? $payload['trackid'] ?? null;

        if (! $trackId) {
            throw new RuntimeException('Rajhi webhook: missing trackId');
        }

        $payment = Payment::find($trackId);

        if (! $payment) {
            throw new RuntimeException("Rajhi webhook: payment not found [{$trackId}]");
        }

        // Idempotency guard
        if ($payment->status !== PaymentStatusEnum::Pending) {
            return;
        }

        $result = $this->callbackAction->handle($payment, $payload);

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
