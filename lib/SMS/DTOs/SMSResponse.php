<?php

declare(strict_types=1);

namespace Lib\SMS\DTOs;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Data Transfer Object representing a SMS response.
 */
final readonly class SMSResponse implements Arrayable
{
    public function __construct(
        protected string $status,
        protected string $driver,
        protected string $message = '',
        protected array $data = [],

    ) {}

    public function getStatus(): string
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'driver' => $this->driver,
            'data' => $this->data,
            'message' => $this->message,
        ];
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }
}
