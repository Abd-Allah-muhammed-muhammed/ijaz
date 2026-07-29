<?php

namespace Modules\Cms\Actions\Question;

use Modules\Cms\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Cms\Models\Question;

class ShowQuestionAction
{
    public function __construct(
        private readonly QuestionRepositoryInterface $repository,
    ) {}

    public function handle(Question $question): Question
    {
        return $this->repository->loadForEdit($question);
    }
}
