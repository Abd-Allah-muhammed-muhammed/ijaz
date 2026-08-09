<?php

namespace Modules\Reviews\Actions\Review;

use Modules\Reviews\Contracts\Repositories\ReviewRepositoryInterface;
use Modules\Reviews\DTOs\CreateReviewDTO;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Notifications\ReviewReceivedNotification;

class CreateOrUpdateReviewAction
{
    public function __construct(
        private readonly ReviewRepositoryInterface $repository,
    ) {}

    public function handle(CreateReviewDTO $dto): Review
    {
        $review = $this->repository->createOrUpdate(
            [
                'type' => $dto->reviewer::class,
                'id' => $dto->reviewer->getKey(),
            ],
            [
                'type' => $dto->reviewee::class,
                'id' => $dto->reviewee->getKey(),
            ],
            [
                'type' => $dto->operation::class,
                'id' => $dto->operation->getKey(),
            ],
            $dto->rating,
            $dto->comment,
        );

        $reviewee = $dto->reviewee;
        $reviewee->notify(new ReviewReceivedNotification($review));

        return $review;
    }
}
