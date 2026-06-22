<?php

namespace Modules\Payment\DTOs;

/**
 * Result of a payment initiation (gateway::initiate()).
 * Returned to API callers via PaymentService::initiate().
 *
 * @see PaymentResponse for admin/provider display use cases
 */
final readonly class PaymentInitResult
{
    public function __construct(
        public string $status,
        public string $driver,
        public string $url,
        public bool $payable,
        public ?string $transactionId = null,
        public ?string $message = null,
        public array $data = [],
    ) {}

    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'driver' => $this->driver,
            'url' => $this->url,
            'payable' => $this->payable,
            'transaction_id' => $this->transactionId,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
