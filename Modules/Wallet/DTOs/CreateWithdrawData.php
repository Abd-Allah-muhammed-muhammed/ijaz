<?php

namespace Modules\Wallet\DTOs;

final readonly class CreateWithdrawData
{
    public function __construct(
        public float $amount,
        public ?string $userNotes,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            amount: (float) $validated['amount'],
            userNotes: $validated['user_notes'] ?? null,
        );
    }
}
