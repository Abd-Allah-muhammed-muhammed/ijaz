<?php

namespace Modules\Catalog\Actions\CarBrand;

use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\CarBrand;

class ListCarBrandsForSelectAction
{
    /**
     * @return Collection<int, CarBrand>
     */
    public function handle(?string $search = null): Collection
    {
        $base = fn (): Builder => CarBrand::query()->withTranslation();

        if (filled($search)) {
            return $base()
                ->when($search, fn (Builder $query, mixed $v) => $query->whereTranslationLike('name', "%{$v}%"))
                ->get();
        }

        /** @var Collection<int, CarBrand> */
        return LookupCache::rememberForeverForLocale(
            'car-brands:all',
            app()->getLocale(),
            fn (): Collection => $base()->get(),
        );
    }
}
