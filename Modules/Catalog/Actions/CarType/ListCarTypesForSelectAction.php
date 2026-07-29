<?php

namespace Modules\Catalog\Actions\CarType;

use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\CarType;

class ListCarTypesForSelectAction
{
    /**
     * @return Collection<int, CarType>
     */
    public function handle(?string $search = null, int $carBrandId = 0): Collection
    {
        return CarType::query()->withTranslation()
            ->when($search, fn ($query, $v) => $query->whereTranslationLike('name', "%{$v}%"))
            ->when($carBrandId, fn ($query, $v) => $query->where('car_brand_id', $v))
            ->get();
    }
}
