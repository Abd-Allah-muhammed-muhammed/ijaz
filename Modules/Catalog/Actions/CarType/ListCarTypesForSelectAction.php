<?php

namespace Modules\Catalog\Actions\CarType;

use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\CarType;

class ListCarTypesForSelectAction
{
    /**
     * @return Collection<int, CarType>
     */
    public function handle(?string $search = null, int $carBrandId = 0): Collection
    {
        if (filled($search)) {
            return CarType::query()->withTranslation()
                ->when($search, fn ($query, $v) => $query->whereTranslationLike('name', "%{$v}%"))
                ->when($carBrandId, fn ($query, $v) => $query->where('car_brand_id', $v))
                ->get();
        }

        /** @var Collection<int, CarType> */
        return LookupCache::rememberForeverScoped(
            'car-types:by-brand',
            app()->getLocale(),
            $carBrandId,
            fn (): Collection => CarType::query()->withTranslation()
                ->when($carBrandId, fn ($query, $v) => $query->where('car_brand_id', $v))
                ->get(),
        );
    }
}
