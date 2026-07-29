<?php

namespace Modules\Reviews\Actions\Review;

use Modules\Reviews\Contracts\Repositories\ReviewRepositoryInterface;
use Modules\Reviews\Models\Review;

class DeleteReviewAction
{
    public function __construct(
        private readonly ReviewRepositoryInterface $repository,
    ) {}

    public function handle(Review $review): void
    {
        $this->repository->delete($review);
    }
}
