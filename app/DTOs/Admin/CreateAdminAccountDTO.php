<?php

namespace App\DTOs\Admin;

final readonly class CreateAdminAccountDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public string $password,
        public bool $isRoot,
        public ?string $roleName = null,
    ) {}
}
