<?php

namespace App\DTOs\Admin;

use App\Support\Phone;
use Illuminate\Http\UploadedFile;

final readonly class UpdateAdminDTO
{
    /**
     * @param  list<int|string>  $roles
     */
    public function __construct(
        public string $name,
        public string $phone,
        public string $email,
        public ?string $password,
        public string $address,
        public string $job,
        public ?UploadedFile $image,
        public array $roles,
    ) {}

    /**
     * @param  array{name: string, phone: string, email: string, password?: string|null, address: string, job: string, roles: list<int|string>}  $validated
     */
    public static function fromValidated(array $validated, ?UploadedFile $image): self
    {
        return new self(
            name: $validated['name'],
            phone: Phone::make($validated['phone'])->toString(),
            email: $validated['email'],
            password: $validated['password'] ?? null,
            address: $validated['address'],
            job: $validated['job'],
            image: $image,
            roles: $validated['roles'],
        );
    }
}
