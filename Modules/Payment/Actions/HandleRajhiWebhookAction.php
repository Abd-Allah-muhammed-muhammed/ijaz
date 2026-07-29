<?php

namespace Modules\Payment\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Payment\Contracts\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;
use RuntimeException;

class HandleRajhiWebhookAction
{
    public function __construct(
        private readonly HandleRajhiCallbackAction $callbackAction,
        private readonly PaymentRepositoryInterface $paymentRepository,
    ) {}

    public function handle(array $payload): void
    {
        // ARB webhook sends data nested under "payLoad" (array), with
        // overall "result" status and "type" at the top level — NOT encrypted.
        // Some integrations flatten the outer array — handle both shapes.
        $body = $payload[0] ?? $payload;

        $payLoad = $body['payLoad'][0] ?? $body['payLoad'] ?? $body;

        $trackId = $payLoad['trackId'] ?? $payLoad['trackid'] ?? null;

        if (! $trackId) {
            throw new RuntimeException('Rajhi webhook: missing trackId');
        }

        $payment = $this->paymentRepository->findById($trackId);

        if (! $payment) {
            throw new RuntimeException("Rajhi webhook: payment not found [{$trackId}]");
        }

        if ($payment->status !== PaymentStatusEnum::Pending) {
            return;
        }

        $topLevelResult = $this->resolveTopLevelResult($body);
        $result = $this->callbackAction->handleWebhookPayload($payLoad, $topLevelResult);

        DB::transaction(function () use ($payment, $result) {
            $this->paymentRepository->updateFromVerifyResult($payment, $result);
        });

        DB::afterCommit(function () use ($payment, $result) {
            $this->paymentRepository->refresh($payment);

            if ($result->isAccepted()) {
                event(new PaymentCompleted($payment));
            } else {
                event(new PaymentFailed($payment));
            }
        });
    }

    private function resolveTopLevelResult(array $body): ?string
    {
        if (! isset($body['result'])) {
            return null;
        }

        $result = $body['result'];

        if (is_array($result)) {
            return $result[0]['status'] ?? $result['status'] ?? null;
        }

        if (is_string($result)) {
            return $result;
        }

        return null;
    }
}
