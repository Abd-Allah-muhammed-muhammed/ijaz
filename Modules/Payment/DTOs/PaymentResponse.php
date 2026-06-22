<?php

declare(strict_types=1);

namespace Modules\Payment\DTOs;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Payment response shape for admin/provider display pages (Inertia).
 * Used by TopUpController show pages.
 *
 * @see PaymentInitResult for API initiation responses
 */
final readonly class PaymentResponse implements Arrayable
{
    public function __construct(
        protected string $status,
        protected string $transactionId,
        protected string $driver,
        protected string $url,
        protected bool $payable,
        protected array $data = [],
        protected ?string $message = null,
    ) {}

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function isPayable(): bool
    {
        return $this->payable;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'transaction_id' => $this->transactionId,
            'driver' => $this->driver,
            'url' => $this->url,
            'payable' => $this->payable,
            'data' => $this->data,
            'message' => $this->message,
        ];
    }
}
