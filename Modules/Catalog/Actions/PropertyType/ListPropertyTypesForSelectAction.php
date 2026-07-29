<?php

namespace Modules\Catalog\Actions\PropertyType;

use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\PropertyType;

class ListPropertyTypesForSelectAction
{
    /**
     * @return Collection<int, PropertyType>
     */
    public function handle(?string $search = null): Collection
    {
        return PropertyType::query()->withTranslation()
            ->when($search, fn ($query, $v) => $query->whereTranslationLike('name', "%{$v}%"))
            ->get();
    }
}
