<?php

namespace Modules\Cms\Actions\Question;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Cms\Contracts\Repositories\QuestionRepositoryInterface;

class ListQuestionsForApiAction
{
    public function __construct(
        private readonly QuestionRepositoryInterface $repository,
    ) {}

    public function handle(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginateForApi($request);
    }
}
