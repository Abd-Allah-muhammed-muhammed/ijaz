<?php

namespace Modules\Catalog\Actions\PropertyCategory;

use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\PropertiyCategory;

class ListPropertyCategoriesForSelectAction
{
    /**
     * @return Collection<int, PropertiyCategory>
     */
    public function handle(?string $search = null): Collection
    {
        return PropertiyCategory::query()->withTranslation()
            ->when($search, fn ($query, $v) => $query->whereTranslationLike('title', "%{$v}%"))
            ->get();
    }
}
