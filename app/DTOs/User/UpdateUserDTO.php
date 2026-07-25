<?php

namespace App\DTOs\User;

use Illuminate\Http\UploadedFile;

final readonly class UpdateUserDTO
{
    public function __construct(
        public string $f_name,
        public string $l_name,
        public string $email,
        public ?string $password,
        public string $phone,
        public int|string $nationality_id,
        public ?UploadedFile $image,
    ) {}

    /**
     * @param  array{f_name: string, l_name: string, email: string, password?: string|null, phone: string, nationality_id: int|string}  $validated
     */
    public static function fromValidated(array $validated, ?UploadedFile $image): self
    {
        return new self(
            f_name: $validated['f_name'],
            l_name: $validated['l_name'],
            email: $validated['email'],
            password: $validated['password'] ?? null,
            phone: $validated['phone'],
            nationality_id: $validated['nationality_id'],
            image: $image,
        );
    }
}
