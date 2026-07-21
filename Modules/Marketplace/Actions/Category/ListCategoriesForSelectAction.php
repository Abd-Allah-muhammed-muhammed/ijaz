<?php

namespace Modules\Marketplace\Actions\Category;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Marketplace\Models\Category;

class ListCategoriesForSelectAction
{
    public function handle(?string $search = null, int $parentId = 0, int $perPage = 10): LengthAwarePaginator
    {
        return Category::query()
            ->withTranslation()
            ->withExists('children')
            ->when(
                $parentId,
                fn ($query, $v) => $query->where('parent_id', $v),
                fn ($query) => $query->whereNull('parent_id')
            )
            ->when(
                $search,
                fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%")
            )
            ->paginate($perPage);
    }
}
