<?php

namespace Modules\Cms\DTOs;

use App\Support\Phone;

final readonly class CreateMessageDTO
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $title,
        public string $content,
    ) {}

    /**
     * @param  array{name: string, phone: string, title: string, content: string}  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            name: $validated['name'],
            phone: Phone::make($validated['phone'])->toString(),
            title: $validated['title'],
            content: $validated['content'],
        );
    }
}
