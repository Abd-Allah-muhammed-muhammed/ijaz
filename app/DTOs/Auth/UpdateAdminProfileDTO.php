<?php

namespace App\DTOs\Auth;

use App\Support\Phone;
use Illuminate\Http\UploadedFile;

final readonly class UpdateAdminProfileDTO
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $email,
        public string $address,
        public string $job,
        public ?string $password,
        public ?UploadedFile $image,
    ) {}

    /**
     * @param  array{name: string, phone: string, email: string, address: string, job: string, password?: string|null}  $validated
     */
    public static function fromValidated(array $validated, ?UploadedFile $image): self
    {
        return new self(
            name: $validated['name'],
            phone: Phone::make($validated['phone'])->toString(),
            email: $validated['email'],
            address: $validated['address'],
            job: $validated['job'],
            password: filled($validated['password'] ?? null) ? (string) $validated['password'] : null,
            image: $image,
        );
    }
}
