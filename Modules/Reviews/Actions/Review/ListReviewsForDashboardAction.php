<?php

namespace Modules\Reviews\Actions\Review;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Reviews\Contracts\Repositories\ReviewRepositoryInterface;

class ListReviewsForDashboardAction
{
    public function __construct(
        private readonly ReviewRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }
}
