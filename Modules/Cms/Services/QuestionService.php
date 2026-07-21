<?php

namespace Modules\Cms\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Cms\Actions\Question\DeleteQuestionAction;
use Modules\Cms\Actions\Question\ListQuestionsAction;
use Modules\Cms\Actions\Question\ListQuestionsForApiAction;
use Modules\Cms\Actions\Question\ShowQuestionAction;
use Modules\Cms\Actions\Question\StoreQuestionAction;
use Modules\Cms\Actions\Question\UpdateQuestionAction;
use Modules\Cms\DTOs\StoreQuestionDTO;
use Modules\Cms\DTOs\UpdateQuestionDTO;
use Modules\Cms\Models\Question;

class QuestionService
{
    public function __construct(
        private readonly ListQuestionsAction $listAction,
        private readonly StoreQuestionAction $storeAction,
        private readonly UpdateQuestionAction $updateAction,
        private readonly DeleteQuestionAction $deleteAction,
        private readonly ShowQuestionAction $showAction,
        private readonly ListQuestionsForApiAction $listForApiAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function store(StoreQuestionDTO $dto): Question
    {
        return $this->storeAction->handle($dto);
    }

    public function update(Question $question, UpdateQuestionDTO $dto): Question
    {
        return $this->updateAction->handle($question, $dto);
    }

    public function destroy(Question $question): void
    {
        $this->deleteAction->handle($question);
    }

    public function show(Question $question): Question
    {
        return $this->showAction->handle($question);
    }

    public function listForApi(Request $request): LengthAwarePaginator
    {
        return $this->listForApiAction->handle($request);
    }
}
