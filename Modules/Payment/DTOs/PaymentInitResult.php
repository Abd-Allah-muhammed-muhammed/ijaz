<?php

namespace Modules\Payment\DTOs;

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
