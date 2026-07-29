<?php

namespace Modules\Cms\Actions\Question;

use Illuminate\Support\Facades\DB;
use Modules\Cms\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Cms\DTOs\UpdateQuestionDTO;
use Modules\Cms\Models\Question;
use Throwable;

class UpdateQuestionAction
{
    public function __construct(
        private readonly QuestionRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Question $question, UpdateQuestionDTO $dto): Question
    {
        return DB::transaction(fn (): Question => $this->repository->update($question, [
            'translations' => $dto->translations,
        ]));
    }
}
