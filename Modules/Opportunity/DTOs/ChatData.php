<?php

namespace Modules\Opportunity\DTOs;

use Modules\Opportunity\Http\Requests\StoreChatRequest;

final readonly class ChatData
{
    public function __construct(
        public string $opportunity_id,
    ) {}

    public static function fromRequest(StoreChatRequest $request): self
    {
        return new self(
            opportunity_id: $request->validated('opportunity_id'),
        );
    }
}
