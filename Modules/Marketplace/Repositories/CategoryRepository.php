<?php

namespace Modules\Marketplace\Repositories;

use App\Services\Normalize\Normalize;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Marketplace\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Marketplace\Exceptions\MarketplaceException;
use Modules\Marketplace\Models\Category;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function paginateForDashboard(Request $request): LengthAwarePaginator
    {
        return Category::withCount(['children'])
            ->with(['translation'])
            ->when($request->input('search'), function ($query, $v) {
                $v = Normalize::make($v, app()->getLocale());

                return $query->whereTranslationLike('normalized_title', "%{$v}%");
            })
            ->when(
                $request->integer('parent_id'),
                fn ($query, $v) => $query->where('parent_id', $v),
                fn ($query) => $query->whereNull('parent_id'),
            )
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function paginateForApi(Request $request): LengthAwarePaginator
    {
        return Category::query()
            ->withTranslation()
            ->with(['children' => function ($query) {
                $query->withTranslation()->limit(6);
            }])
            ->when(
                $request->integer('parent_id'),
                fn ($query, $v) => $query->where('parent_id', $v),
                fn ($query) => $query->whereNull('parent_id')
            )
            ->when(
                $request->search,
                fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
            )
            ->paginate($request->integer('per_page', 10));
    }

    public function paginateWithNoChildrenForApi(Request $request): LengthAwarePaginator
    {
        return Category::query()
            ->withTranslation()
            ->when(
                $request->integer('parent_id'),
                fn ($query, $v) => $query->where('parent_id', $v)
            )
            ->when(
                $request->search,
                fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
            )
            ->whereDoesntHave('children')
            ->paginate($request->integer('per_page', 10));
    }

    public function paginateChildrenForApi(Category $category, Request $request): LengthAwarePaginator
    {
        return $category->children()
            ->withTranslation()
            ->when(
                $request->search,
                fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
            )
            ->paginate($request->integer('per_page', 10));
    }

    public function findById(int $id): Category
    {
        return Category::query()->findOrFail($id);
    }

    public function create(array $data): Category
    {
        return Category::query()->create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->fresh(['translations', 'translation', 'parent']) ?? $category;
    }

    public function delete(Category $category): void
    {
        if ($category->children()->exists()) {
            throw new MarketplaceException(__('this category has subcategories'));
        }

        $category->delete();
        $category->deleteIcon();
    }

    public function loadForEdit(Category $category): Category
    {
        return $category->load(['translations', 'parent']);
    }

    public function getRootCategories(): Collection
    {
        return Category::with(['translations'])
            ->whereNull('parent_id')
            ->get();
    }

    public function getLeafCategories(): Collection
    {
        return Category::with(['translation'])
            ->whereDoesntHave('children')
            ->get();
    }

    public function getAllWithTranslations(): Collection
    {
        return Category::with(['translation'])->get();
    }

    public function getAllExcept(Category $category): Collection
    {
        return Category::with(['translation'])
            ->where('id', '!=', $category->id)
            ->get();
    }
}
