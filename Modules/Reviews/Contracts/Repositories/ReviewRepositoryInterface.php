<?php

namespace Modules\Reviews\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Reviews\Models\Review;

interface ReviewRepositoryInterface
{
    /**
     * @param  array{type: class-string, id: int|string}  $reviewerMorph
     * @param  array{type: class-string, id: int|string}  $revieweeMorph
     * @param  array{type: class-string, id: int|string}  $operationMorph
     */
    public function createOrUpdate(
        array $reviewerMorph,
        array $revieweeMorph,
        array $operationMorph,
        int $rating,
        ?string $comment,
    ): Review;

    public function paginate(Request $request): LengthAwarePaginator;

    public function findById(int $id): ?Review;

    public function delete(Review $review): void;
}
