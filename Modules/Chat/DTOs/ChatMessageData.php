<?php

namespace Modules\Chat\DTOs;

use Illuminate\Http\UploadedFile;

final readonly class ChatMessageData
{
    /**
     * @param  array<int, UploadedFile>|null  $files
     */
    public function __construct(
        public ?string $content = null,
        public ?array $files = null,
    ) {}

    public static function fromRequest(mixed $request): self
    {
        return new self(
            content: $request->validated('content'),
            files: $request->hasFile('files') ? $request->file('files') : null,
        );
    }
}
