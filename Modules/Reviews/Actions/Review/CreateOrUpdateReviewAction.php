<?php

namespace Modules\Reviews\Actions\Review;

use Modules\Reviews\Contracts\Repositories\ReviewRepositoryInterface;
use Modules\Reviews\DTOs\CreateReviewDTO;
use Modules\Reviews\Models\Review;

class CreateOrUpdateReviewAction
{
    public function __construct(
        private readonly ReviewRepositoryInterface $repository,
    ) {}

    public function handle(CreateReviewDTO $dto): Review
    {
        return $this->repository->createOrUpdate(
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
    }
}
