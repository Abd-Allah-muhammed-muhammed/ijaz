<?php

namespace Modules\Orders\DTOs;

use Illuminate\Http\UploadedFile;

final readonly class UpdateOrderDTO
{
    /**
     * @param  array<string, mixed>  $attributes  Validated attributes for mass-assignment.
     * @param  array<int, UploadedFile>|null  $files
     */
    public function __construct(
        public array $attributes,
        public ?array $files = null,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<int, UploadedFile>|null  $files
     */
    public static function fromValidated(array $validated, ?array $files = null): self
    {
        return new self(
            attributes: $validated,
            files: $files,
        );
    }
}
