<?php

namespace App\DTOs\Auth;

final readonly class ProviderLoginResult
{
    public function __construct(
        public string $redirectRouteName,
    ) {}
}
