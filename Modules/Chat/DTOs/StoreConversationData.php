<?php

namespace Modules\Chat\DTOs;

final readonly class StoreConversationData
{
    public function __construct(
        public string $operation_id,
        public string $operation_type,
    ) {}

    public static function fromRequest(mixed $request): self
    {
        return new self(
            operation_id: $request->validated('operation_id'),
            operation_type: $request->validated('operation_type'),
        );
    }
}
