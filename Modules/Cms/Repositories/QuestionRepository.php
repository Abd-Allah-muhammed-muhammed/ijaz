<?php

namespace Modules\Cms\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Cms\Contracts\Repositories\QuestionRepositoryInterface;
use Modules\Cms\Models\Question;

class QuestionRepository implements QuestionRepositoryInterface
{
    public function paginateForDashboard(Request $request): LengthAwarePaginator
    {
        return Question::query()
            ->with('translations')
            ->when($request->search, fn (Builder $query, mixed $search) => $query->whereTranslationLike('title', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function paginateForApi(Request $request): LengthAwarePaginator
    {
        return Question::query()
            ->withTranslation()
            ->when($request->search, fn (Builder $query, mixed $search) => $query->whereTranslationLike('title', "%{$search}%"))
            ->paginate($request->integer('per_page', 10));
    }

    public function create(array $data): Question
    {
        return Question::query()->create($data);
    }

    public function update(Question $question, array $data): Question
    {
        $question->update($data);

        return $question->fresh(['translations']) ?? $question;
    }

    public function delete(Question $question): void
    {
        $question->delete();
    }

    public function loadForEdit(Question $question): Question
    {
        return $question->load(['translations']);
    }
}
