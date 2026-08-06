<?php

namespace App\DTOs\DeviceToken;

final readonly class BackfillDeviceTokensResult
{
    public function __construct(
        public int $migrated,
        public int $skipped,
        public int $conflicts,
    ) {}

    public function totalConsidered(): int
    {
        return $this->migrated + $this->skipped + $this->conflicts;
    }
}
