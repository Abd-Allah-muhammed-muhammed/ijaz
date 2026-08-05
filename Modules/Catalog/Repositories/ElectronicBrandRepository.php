<?php

namespace Modules\Catalog\Repositories;

use App\Support\LookupCache;
use App\Support\TranslationSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\ElectronicBrandRepositoryInterface;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Catalog\QueryFilters\ElectronicBrand\ElectronicBrandFilters;

class ElectronicBrandRepository implements ElectronicBrandRepositoryInterface
{
    public function query(): Builder
    {
        return ElectronicBrand::with(['translation']);
    }

    public function paginate(Request $request): LengthAwarePaginator
    {
        $filters = new ElectronicBrandFilters($request);

        return $this->query()
            ->tap(fn (Builder $query) => $filters->apply($query))
            ->paginate($filters->perPage())
            ->withQueryString();
    }

    /**
     * @return Collection<int, ElectronicBrand>
     */
    public function getAll(Request $request): Collection
    {
        // Prefer the shared select/cache path (ListElectronicBrandsForSelectAction).
        // Kept for interface compatibility; empty-search hits the same LookupCache key.
        if (filled($request->search)) {
            return ElectronicBrand::with(['translation'])
                ->where('is_active', true)
                ->when(
                    $request->search,
                    fn (Builder $query, mixed $value) => TranslationSearch::apply($query, (string) $value, 'normalized_name')
                )
                ->get();
        }

        /** @var Collection<int, ElectronicBrand> */
        return LookupCache::rememberForeverForLocale(
            'electronic-brands:all',
            app()->getLocale(),
            fn (): Collection => ElectronicBrand::query()->withTranslation()
                ->where('is_active', true)
                ->get(),
        );
    }

    public function create(array $data): ElectronicBrand
    {
        return ElectronicBrand::create($data);
    }

    public function update(ElectronicBrand $electronicBrand, array $data): ElectronicBrand
    {
        $electronicBrand->update($data);

        return $electronicBrand->fresh();
    }

    public function delete(ElectronicBrand $electronicBrand): void
    {
        $electronicBrand->deleteImage();
        $electronicBrand->delete();
    }

    public function findById(int $id): ElectronicBrand
    {
        return ElectronicBrand::findOrFail($id);
    }

    public function find(int $id): ?ElectronicBrand
    {
        return ElectronicBrand::find($id);
    }

    public function updateStatus(ElectronicBrand $electronicBrand, bool $isActive): ElectronicBrand
    {
        $electronicBrand->update(['is_active' => $isActive]);

        return $electronicBrand->fresh();
    }
}
