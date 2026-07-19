<?php

namespace App\DTOs\Auth;

final readonly class LoginResult
{
    public function __construct(
        public string $redirectRouteName,
    ) {}
}
