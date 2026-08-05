<?php

namespace Modules\Catalog\Actions\ElectronicBrand;

use App\Support\LookupCache;
use App\Support\TranslationSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Catalog\Models\ElectronicBrand;

class ListElectronicBrandsForSelectAction
{
    /**
     * @return Collection<int, ElectronicBrand>
     */
    public function handle(?string $search = null): Collection
    {
        $base = fn (): Builder => ElectronicBrand::query()
            ->withTranslation()
            ->where('is_active', true);

        if (filled($search)) {
            return $base()
                ->when($search, fn (Builder $query, mixed $v) => TranslationSearch::apply($query, (string) $v, 'normalized_name'))
                ->get();
        }

        /** @var Collection<int, ElectronicBrand> */
        return LookupCache::rememberForeverForLocale(
            'electronic-brands:all',
            app()->getLocale(),
            fn (): Collection => $base()->get(),
        );
    }
}
