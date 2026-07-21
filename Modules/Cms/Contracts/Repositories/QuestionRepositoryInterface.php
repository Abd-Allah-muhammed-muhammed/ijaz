<?php

namespace Modules\Cms\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Cms\Models\Question;

interface QuestionRepositoryInterface
{
    public function paginateForDashboard(Request $request): LengthAwarePaginator;

    public function paginateForApi(Request $request): LengthAwarePaginator;

    public function create(array $data): Question;

    public function update(Question $question, array $data): Question;

    public function delete(Question $question): void;

    public function loadForEdit(Question $question): Question;
}
