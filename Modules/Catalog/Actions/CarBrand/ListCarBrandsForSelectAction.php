<?php

namespace Modules\Catalog\Actions\CarBrand;

use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\CarBrand;

class ListCarBrandsForSelectAction
{
    /**
     * @return Collection<int, CarBrand>
     */
    public function handle(?string $search = null): Collection
    {
        if (filled($search)) {
            return CarBrand::query()->withTranslation()
                ->when($search, fn ($query, $v) => $query->whereTranslationLike('name', "%{$v}%"))
                ->get();
        }

        /** @var Collection<int, CarBrand> */
        return LookupCache::rememberForeverForLocale(
            'car-brands:all',
            app()->getLocale(),
            fn (): Collection => CarBrand::query()->withTranslation()->get(),
        );
    }
}
