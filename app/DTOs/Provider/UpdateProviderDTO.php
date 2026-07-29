<?php

namespace App\DTOs\Provider;

use Illuminate\Http\UploadedFile;

final readonly class UpdateProviderDTO
{
    /**
     * @param  list<array{id: int|string, skills?: list<int|string>|null}>  $categories
     * @param  array<string, UploadedFile>  $mediaFiles
     */
    public function __construct(
        public string $name,
        public int|string $provider_type_id,
        public int|string $region_id,
        public int|string $city_id,
        public string $address,
        public string $phone,
        public string $email,
        public string $iban,
        public string $about,
        public ?string $password,
        public ?UploadedFile $logo,
        public array $categories,
        public array $mediaFiles,
    ) {}

    /**
     * @param  array{name: string, provider_type_id: int|string, region_id: int|string, city_id: int|string, address: string, phone: string, email: string, iban: string, about: string, password?: string|null, categories: list<array{id: int|string, skills?: list<int|string>|null}>}  $validated
     * @param  array<string, UploadedFile>  $mediaFiles
     */
    public static function fromValidated(array $validated, ?UploadedFile $logo, array $mediaFiles): self
    {
        return new self(
            name: $validated['name'],
            provider_type_id: $validated['provider_type_id'],
            region_id: $validated['region_id'],
            city_id: $validated['city_id'],
            address: $validated['address'],
            phone: $validated['phone'],
            email: $validated['email'],
            iban: $validated['iban'],
            about: $validated['about'],
            password: $validated['password'] ?? null,
            logo: $logo,
            categories: $validated['categories'],
            mediaFiles: $mediaFiles,
        );
    }
}
