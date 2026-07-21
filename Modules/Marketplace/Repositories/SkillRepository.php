<?php

namespace Modules\Marketplace\Repositories;

use App\Services\Normalize\Normalize;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Marketplace\Contracts\Repositories\SkillRepositoryInterface;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\Skill;

class SkillRepository implements SkillRepositoryInterface
{
    public function paginateForDashboard(Request $request): LengthAwarePaginator
    {
        return Skill::with(['translation', 'category'])
            ->when($request->integer('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->integer('category_id'));
            })
            ->when($request->input('search'), function ($query, $v) {
                $v = Normalize::make($v, app()->getLocale());

                return $query->whereTranslationLike('normalized_title', "%{$v}%");
            })
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function paginateForApi(Request $request, ?int $categoryId = null): LengthAwarePaginator
    {
        if ($categoryId === null) {
            return Skill::query()
                ->when(
                    $request->category_id,
                    fn ($query, $v) => $query->where('category_id', $v)
                )
                ->withTranslation()
                ->when(
                    $request->search,
                    fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
                )
                ->paginate($request->integer('per_page', 10));
        }

        $category = Category::findOrFail($categoryId);

        return $category->skills()
            ->withTranslation()
            ->when(
                $request->search,
                fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
            )
            ->paginate($request->integer('per_page', 10));
    }

    public function findById(int $id): Skill
    {
        return Skill::query()->findOrFail($id);
    }

    public function create(array $data): Skill
    {
        return Skill::query()->create($data);
    }

    public function update(Skill $skill, array $data): Skill
    {
        $skill->update($data);

        return $skill->fresh(['translations', 'translation', 'category']) ?? $skill;
    }

    public function delete(Skill $skill): void
    {
        $skill->delete();
    }

    public function loadForEdit(Skill $skill): Skill
    {
        return $skill->load(['translations', 'category']);
    }
}
