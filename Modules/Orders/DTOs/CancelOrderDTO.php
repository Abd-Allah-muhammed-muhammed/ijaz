<?php

namespace Modules\Orders\DTOs;

final readonly class CancelOrderDTO
{
    public function __construct(
        public string $reason,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            reason: (string) $validated['reason'],
        );
    }
}
