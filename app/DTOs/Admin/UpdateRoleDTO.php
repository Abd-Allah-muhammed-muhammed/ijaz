<?php

namespace App\DTOs\Admin;

final readonly class UpdateRoleDTO
{
    /**
     * @param  list<int|string>  $permissions
     */
    public function __construct(
        public string $name,
        public array $permissions,
    ) {}

    /**
     * @param  array{name: string, permissions: list<int|string>}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            name: $validated['name'],
            permissions: $validated['permissions'],
        );
    }
}
