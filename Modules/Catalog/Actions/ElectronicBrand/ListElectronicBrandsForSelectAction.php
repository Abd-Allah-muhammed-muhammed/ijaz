<?php

namespace Modules\Catalog\Actions\ElectronicBrand;

use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\ElectronicBrand;

class ListElectronicBrandsForSelectAction
{
    /**
     * @return Collection<int, ElectronicBrand>
     */
    public function handle(?string $search = null): Collection
    {
        return ElectronicBrand::query()->withTranslation()
            ->where('is_active', true)
            ->when($search, fn ($query, $v) => $query->whereTranslationLike('name', "%{$v}%"))
            ->get();
    }
}
