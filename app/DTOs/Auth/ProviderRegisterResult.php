<?php

namespace App\DTOs\Auth;

use App\Models\Provider;

final readonly class ProviderRegisterResult
{
    private function __construct(
        public bool $success,
        public ?Provider $provider = null,
        public ?string $errorMessage = null,
    ) {}

    public static function success(Provider $provider): self
    {
        return new self(success: true, provider: $provider);
    }

    public static function failed(string $errorMessage): self
    {
        return new self(success: false, errorMessage: $errorMessage);
    }
}
