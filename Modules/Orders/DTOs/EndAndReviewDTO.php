<?php

namespace Modules\Orders\DTOs;

final readonly class EndAndReviewDTO
{
    public function __construct(
        public int $rating,
        public string $comment,
    ) {}

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function fromValidated(array $validated): self
    {
        return new self(
            rating: (int) $validated['rating'],
            comment: (string) $validated['comment'],
        );
    }
}
