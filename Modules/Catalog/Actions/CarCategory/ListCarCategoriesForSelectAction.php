<?php

namespace Modules\Catalog\Actions\CarCategory;

use App\Support\TranslationSearch;
use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\CarCategory;

class ListCarCategoriesForSelectAction
{
    /**
     * @return Collection<int, CarCategory>
     */
    public function handle(?string $search = null): Collection
    {
        return CarCategory::query()->withTranslation()
            ->when($search, fn ($query, $v) => TranslationSearch::apply($query, (string) $v))
            ->get();
    }
}
