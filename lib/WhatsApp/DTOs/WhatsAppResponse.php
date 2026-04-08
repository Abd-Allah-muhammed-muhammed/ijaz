<?php

declare(strict_types=1);

namespace Lib\WhatsApp\DTOs;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Data Transfer Object representing a WhatsApp response.
 */
final readonly class WhatsAppResponse implements Arrayable
{
    public function __construct(
        protected string $status,
        protected string $driver,
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
        ];
    }
}
