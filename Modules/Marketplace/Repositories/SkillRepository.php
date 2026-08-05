<?php

namespace Modules\Marketplace\Repositories;

use App\Support\LookupCache;
use App\Support\TranslationSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Marketplace\Contracts\Repositories\SkillRepositoryInterface;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\Skill;

class SkillRepository implements SkillRepositoryInterface
{
    /**
     * @return Collection<int, Skill>
     */
    public function listForSelect(?string $search = null, int $categoryId = 0): Collection
    {
        // Preserve historical behaviour: always filter by category_id (including 0).
        if (filled($search)) {
            return Skill::query()->withTranslation()
                ->when($search, fn ($query, $v) => TranslationSearch::apply($query, (string) $v))
                ->where('category_id', $categoryId)
                ->get();
        }

        /** @var Collection<int, Skill> */
        return LookupCache::rememberForeverScoped(
            'skills:by-category',
            app()->getLocale(),
            $categoryId,
            fn (): Collection => Skill::query()->withTranslation()
                ->where('category_id', $categoryId)
                ->get(),
        );
    }

    public function paginateForDashboard(Request $request): LengthAwarePaginator
    {
        return Skill::with(['translation', 'category.translation'])
            ->when($request->integer('category_id'), function ($query) use ($request) {
                $query->where('category_id', $request->integer('category_id'));
            })
            ->when(
                $request->input('search'),
                fn ($query, $v) => TranslationSearch::apply($query, (string) $v)
            )
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
                    fn ($query, $v) => TranslationSearch::apply($query, (string) $v)
                )
                ->paginate($request->integer('per_page', 10));
        }

        $category = Category::findOrFail($categoryId);

        return $category->skills()
            ->withTranslation()
            ->when(
                $request->search,
                fn ($query, $v) => TranslationSearch::apply($query, (string) $v)
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

        return $skill->fresh(['translations', 'translation', 'category.translation']) ?? $skill;
    }

    public function delete(Skill $skill): void
    {
        $skill->delete();
    }

    public function loadForEdit(Skill $skill): Skill
    {
        return $skill->load(['translations', 'category.translation']);
    }
}
