<?php

namespace App\DTOs\Auth;

use App\Enums\Providers\ProviderStatusEnum;

/**
 * Props for the provider account-status gate Inertia page.
 * Fresh server-side lookup — never trust URL/session for status/reason.
 */
final readonly class ProviderAccountStatusGateDTO
{
    public function __construct(
        public ProviderStatusEnum $status,
        public ?string $reason,
        public ?string $blockedUntil,
        public bool $isTemporaryBlock,
        public ?string $blockReason,
    ) {}

    /**
     * @return array{
     *     status: array{value: string, label: string, color: string},
     *     reason: string|null,
     *     blocked_until: string|null,
     *     is_temporary_block: bool,
     *     block_reason: string|null,
     * }
     */
    public function toInertiaProps(): array
    {
        return [
            'status' => $this->status->toArray(),
            'reason' => $this->reason,
            'blocked_until' => $this->blockedUntil,
            'is_temporary_block' => $this->isTemporaryBlock,
            'block_reason' => $this->blockReason,
        ];
    }
}
