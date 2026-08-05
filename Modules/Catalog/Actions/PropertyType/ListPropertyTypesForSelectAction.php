<?php

namespace Modules\Catalog\Actions\PropertyType;

use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\PropertyType;

class ListPropertyTypesForSelectAction
{
    /**
     * @return Collection<int, PropertyType>
     */
    public function handle(?string $search = null): Collection
    {
        $base = fn (): Builder => PropertyType::query()->withTranslation();

        if (filled($search)) {
            return $base()
                ->when($search, fn (Builder $query, mixed $v) => $query->whereTranslationLike('name', "%{$v}%"))
                ->get();
        }

        /** @var Collection<int, PropertyType> */
        return LookupCache::rememberForeverForLocale(
            'property-types:all',
            app()->getLocale(),
            fn (): Collection => $base()->get(),
        );
    }
}
