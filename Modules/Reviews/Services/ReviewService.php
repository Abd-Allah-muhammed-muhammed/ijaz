<?php

namespace Modules\Reviews\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Reviews\Actions\Review\CreateOrUpdateReviewAction;
use Modules\Reviews\Actions\Review\DeleteReviewAction;
use Modules\Reviews\Actions\Review\ListReviewsForDashboardAction;
use Modules\Reviews\DTOs\CreateReviewDTO;
use Modules\Reviews\Models\Review;

class ReviewService
{
    public function __construct(
        private readonly CreateOrUpdateReviewAction $createOrUpdateAction,
        private readonly ListReviewsForDashboardAction $listForDashboardAction,
        private readonly DeleteReviewAction $deleteAction,
    ) {}

    public function submit(
        Model $reviewer,
        Model $reviewee,
        Model $operation,
        int $rating,
        ?string $comment,
    ): Review {
        return $this->createOrUpdateAction->handle(new CreateReviewDTO(
            reviewer: $reviewer,
            reviewee: $reviewee,
            operation: $operation,
            rating: $rating,
            comment: $comment,
        ));
    }

    public function paginateForDashboard(Request $request): LengthAwarePaginator
    {
        return $this->listForDashboardAction->handle($request);
    }

    public function delete(Review $review): void
    {
        $this->deleteAction->handle($review);
    }
}
