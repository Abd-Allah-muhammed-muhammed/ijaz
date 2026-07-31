<?php

namespace Modules\Catalog\Actions\PropertyCategory;

use App\Support\TranslationSearch;
use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\PropertyCategory;

class ListPropertyCategoriesForSelectAction
{
    /**
     * @return Collection<int, PropertyCategory>
     */
    public function handle(?string $search = null): Collection
    {
        return PropertyCategory::query()->withTranslation()
            ->when($search, fn ($query, $v) => TranslationSearch::apply($query, (string) $v))
            ->get();
    }
}
