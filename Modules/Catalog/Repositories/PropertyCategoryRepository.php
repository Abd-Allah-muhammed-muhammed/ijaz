<?php

namespace Modules\Catalog\Repositories;

use App\Services\Normalize\Normalize;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\PropertyCategoryRepositoryInterface;
use Modules\Catalog\Models\PropertiyCategory;
use Modules\Catalog\QueryFilters\PropertyCategory\PropertyCategoryFilters;

class PropertyCategoryRepository implements PropertyCategoryRepositoryInterface
{
    public function paginate(PropertyCategoryFilters $filters): LengthAwarePaginator
    {
        return $filters->apply(PropertiyCategory::query()->withCount(['children'])->with(['translations']))
            ->paginate($filters->perPage())
            ->withQueryString();
    }

    public function paginateForDashboard(Request $request): LengthAwarePaginator
    {
        return PropertiyCategory::withCount(['children'])
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

    public function findById(int $id): PropertiyCategory
    {
        return PropertiyCategory::query()->findOrFail($id);
    }

    public function create(array $data): PropertiyCategory
    {
        return PropertiyCategory::query()->create($data);
    }

    public function update(PropertiyCategory $propertyCategory, array $data): PropertiyCategory
    {
        $propertyCategory->update($data);

        return $propertyCategory->fresh(['translations', 'translation', 'parent']) ?? $propertyCategory;
    }

    public function delete(PropertiyCategory $propertyCategory): void
    {
        if ($propertyCategory->children()->exists()) {
            throw new Exception(__('this category has subcategories'));
        }

        $propertyCategory->delete();
    }

    public function loadForEdit(PropertiyCategory $propertyCategory): PropertiyCategory
    {
        return $propertyCategory->load(['translations', 'parent']);
    }

    /**
     * @return Collection<int, PropertiyCategory>
     */
    public function getRootCategories(): Collection
    {
        return PropertiyCategory::with(['translation'])
            ->whereNull('parent_id')
            ->get();
    }
}
