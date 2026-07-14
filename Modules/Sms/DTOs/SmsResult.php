<?php

namespace Modules\Sms\DTOs;

/**
 * Result of an SMS send (gateway::send() / sendMany()).
 */
final readonly class SmsResult
{
    public function __construct(
        public string $status,
        public string $driver,
        public string $message = '',
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
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
