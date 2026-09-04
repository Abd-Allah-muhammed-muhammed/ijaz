<?php

namespace App\DTOs\Provider;

final readonly class UpdateProviderStatusDTO
{
    public function __construct(
        public string $status,
        public ?int $blockDays,
        public ?string $blockReason,
        public ?string $reason = null,
    ) {}

    /**
     * @param  array{status: string, block_days?: int|string|null, block_reason?: string|null, reason?: string|null}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            status: (string) $validated['status'],
            blockDays: isset($validated['block_days']) ? (int) $validated['block_days'] : null,
            blockReason: $validated['block_reason'] ?? null,
            reason: $validated['reason'] ?? null,
        );
    }
}
