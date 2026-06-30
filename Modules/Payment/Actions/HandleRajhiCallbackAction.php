<?php

namespace Modules\Payment\Actions;

use Modules\Payment\DTOs\PaymentVerifyResult;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\RajhiEncryptionService;
use Throwable;

class HandleRajhiCallbackAction
{
    public function __construct(
        private readonly RajhiEncryptionService $encryption,
    ) {}

    public function handle(Payment $payment, array $payload): PaymentVerifyResult
    {
        // Callback sends encrypted trandata on redirect.
        // Webhook uses plain payLoad via handleWebhookPayload().

        $trandata = $payload['trandata'] ?? null;

        if ($trandata) {
            return $this->handleEncrypted($trandata, $payload);
        }

        // Fallback: direct fields (no trandata)
        return $this->handleDirectFields($payload);
    }

    private function handleEncrypted(string $trandata, array $payload): PaymentVerifyResult
    {
        try {
            $decrypted = $this->encryption->decrypt($trandata);
            $result = $this->mapResult($decrypted);

            return new PaymentVerifyResult(
                status: $result->status,
                transactionId: $result->transactionId,
                rawResponse: $payload,
                message: $result->message,
            );
        } catch (Throwable $e) {
            return new PaymentVerifyResult(
                status: PaymentStatusEnum::Rejected,
                message: 'Decryption failed: '.$e->getMessage(),
                rawResponse: $payload,
            );
        }
    }

    private function handleDirectFields(array $payload): PaymentVerifyResult
    {
        return $this->mapResult($payload);
    }

    public function handleWebhookPayload(array $payLoad, ?string $resultOverride = null): PaymentVerifyResult
    {
        return $this->mapResult($payLoad, $resultOverride);
    }

    private function mapResult(array $data, ?string $resultOverride = null): PaymentVerifyResult
    {
        $result = strtoupper($resultOverride ?? $data['result'] ?? '');
        $transId = (string) ($data['transId'] ?? $data['tranId'] ?? '');
        $authRespCode = $data['authRespCode'] ?? null;

        // From Neoleap docs:
        // CAPTURED       → success
        // NOT CAPTURED   → failure
        // DENIED BY RISK → failure
        // HOST TIMEOUT   → pending
        // PROCESSING     → pending
        $status = match (true) {
            in_array($result, ['CAPTURED', 'APPROVED'], true) => PaymentStatusEnum::Accepted,
            in_array($result, ['HOST TIMEOUT', 'PROCESSING'], true) => PaymentStatusEnum::Pending,
            in_array($result, ['VOIDED'], true) => PaymentStatusEnum::Canceled,
            default => PaymentStatusEnum::Rejected,
        };

        $message = $this->buildMessage($data, $authRespCode);

        return new PaymentVerifyResult(
            status: $status,
            transactionId: $transId ?: null,
            rawResponse: $data,
            message: $message,
        );
    }

    private function buildMessage(array $data, ?string $authRespCode): ?string
    {
        if (! empty($data['errorText'])) {
            return $data['errorText'];
        }

        if ($authRespCode === null) {
            return null;
        }

        $description = $this->describeAuthRespCode($authRespCode);

        return $description !== null
            ? "{$authRespCode} — {$description}"
            : $authRespCode;
    }

    private function describeAuthRespCode(?string $code): ?string
    {
        return match ($code) {
            '00' => 'Approved',
            'N7' => 'CVV2/CVC2 mismatch — incorrect CVV entered',
            '05' => 'Do not honor',
            '51' => 'Insufficient funds',
            '54' => 'Expired card',
            '14' => 'Invalid card number',
            default => null,
        };
    }
}
