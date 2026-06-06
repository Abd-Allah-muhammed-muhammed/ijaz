<?php

namespace Modules\Opportunity\DTOs;

use Modules\Opportunity\Http\Requests\StoreCommentRequest;

final readonly class CommentData
{
    public function __construct(
        public string $body,
    ) {}

    public static function fromRequest(StoreCommentRequest $request): self
    {
        return new self(
            body: (string) $request->validated('body'),
        );
    }
}
