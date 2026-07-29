<?php

namespace Modules\Catalog\Actions\CarBrand;

use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\CarBrand;

class ListCarBrandsForSelectAction
{
    /**
     * @return Collection<int, CarBrand>
     */
    public function handle(?string $search = null): Collection
    {
        return CarBrand::query()->withTranslation()
            ->when($search, fn ($query, $v) => $query->whereTranslationLike('name', "%{$v}%"))
            ->get();
    }
}
