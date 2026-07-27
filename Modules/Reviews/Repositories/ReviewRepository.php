<?php

namespace Modules\Reviews\Repositories;

use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Reviews\Contracts\Repositories\ReviewRepositoryInterface;
use Modules\Reviews\Models\Review;

class ReviewRepository implements ReviewRepositoryInterface
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
    ): Review {
        return Review::query()->updateOrCreate(
            [
                'reviewer_type' => $reviewerMorph['type'],
                'reviewer_id' => $reviewerMorph['id'],
                'operation_type' => $operationMorph['type'],
                'operation_id' => $operationMorph['id'],
            ],
            [
                'reviewee_type' => $revieweeMorph['type'],
                'reviewee_id' => $revieweeMorph['id'],
                'rating' => $rating,
                'comment' => $comment,
            ],
        );
    }

    public function paginate(Request $request): LengthAwarePaginator
    {
        return Review::query()
            ->with(['reviewer', 'reviewee'])
            ->when($request->input('search'), function (Builder $query, mixed $value) {
                return $query->where('comment', 'like', '%'.(string) $value.'%');
            })
            ->when($request->filled('rating'), function (Builder $query) use ($request) {
                return $query->where('rating', $request->integer('rating'));
            })
            ->when($request->input('reviewer_type'), function (Builder $query, mixed $value) {
                $type = $this->resolveMorphType((string) $value);

                return $type ? $query->where('reviewer_type', $type) : $query;
            })
            ->when($request->input('reviewee_type'), function (Builder $query, mixed $value) {
                $type = $this->resolveMorphType((string) $value);

                return $type ? $query->where('reviewee_type', $type) : $query;
            })
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function findById(int $id): ?Review
    {
        return Review::query()->find($id);
    }

    public function delete(Review $review): void
    {
        $review->delete();
    }

    private function resolveMorphType(string $value): ?string
    {
        return match (str($value)->afterLast('\\')->toString()) {
            'User' => User::class,
            'Provider' => Provider::class,
            default => class_exists($value) ? $value : null,
        };
    }
}
