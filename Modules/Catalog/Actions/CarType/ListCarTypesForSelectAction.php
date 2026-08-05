<?php

namespace Modules\Catalog\Actions\CarType;

use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\CarType;

class ListCarTypesForSelectAction
{
    /**
     * @return Collection<int, CarType>
     */
    public function handle(?string $search = null, int $carBrandId = 0): Collection
    {
        $base = fn (): Builder => CarType::query()
            ->withTranslation()
            ->when($carBrandId, fn (Builder $query, mixed $v) => $query->where('car_brand_id', $v));

        if (filled($search)) {
            return $base()
                ->when($search, fn (Builder $query, mixed $v) => $query->whereTranslationLike('name', "%{$v}%"))
                ->get();
        }

        /** @var Collection<int, CarType> */
        return LookupCache::rememberForeverScoped(
            'car-types:by-brand',
            app()->getLocale(),
            $carBrandId,
            fn (): Collection => $base()->get(),
        );
    }
}
