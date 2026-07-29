<?php

namespace Modules\Reviews\DTOs;

use Illuminate\Database\Eloquent\Model;

final readonly class CreateReviewDTO
{
    public function __construct(
        public Model $reviewer,
        public Model $reviewee,
        public Model $operation,
        public int $rating,
        public ?string $comment,
    ) {}
}
