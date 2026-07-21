<?php

namespace Modules\Cms\Actions\Question;

use Modules\Cms\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Cms\Models\Question;

class DeleteQuestionAction
{
    public function __construct(
        private readonly QuestionRepositoryInterface $repository,
    ) {}

    public function handle(Question $question): void
    {
        $this->repository->delete($question);
    }
}
