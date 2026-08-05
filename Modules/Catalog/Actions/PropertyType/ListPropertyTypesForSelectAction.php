<?php

namespace Modules\Catalog\Actions\PropertyType;

use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\PropertyType;

class ListPropertyTypesForSelectAction
{
    /**
     * @return Collection<int, PropertyType>
     */
    public function handle(?string $search = null): Collection
    {
        if (filled($search)) {
            return PropertyType::query()->withTranslation()
                ->when($search, fn ($query, $v) => $query->whereTranslationLike('name', "%{$v}%"))
                ->get();
        }

        /** @var Collection<int, PropertyType> */
        return LookupCache::rememberForeverForLocale(
            'property-types:all',
            app()->getLocale(),
            fn (): Collection => PropertyType::query()->withTranslation()->get(),
        );
    }
}
