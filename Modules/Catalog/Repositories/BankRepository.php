<?php

namespace Modules\Catalog\Repositories;

use App\Support\LookupCache;
use App\Support\TranslationSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\BankRepositoryInterface;
use Modules\Catalog\Models\Bank;

class BankRepository implements BankRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return Bank::query()
            ->with(['translation', 'media'])
            ->when(
                $request->input('search'),
                fn (Builder $query, mixed $value) => TranslationSearch::apply($query, (string) $value, 'normalized_name')
            )
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function findById(int $id): Bank
    {
        return Bank::query()->findOrFail($id);
    }

    public function create(array $attributes): Bank
    {
        return Bank::query()->create($attributes);
    }

    public function update(Bank $bank, array $attributes): Bank
    {
        $bank->update($attributes);

        return $bank->fresh(['translations', 'translation', 'media']) ?? $bank;
    }

    public function delete(Bank $bank): void
    {
        // Soft delete only — keep logo/media so historical Guarantor/CarAdvisement
        // relations can still resolve name + logo via withTrashed().
        $bank->delete();
    }

    public function loadForEdit(Bank $bank): Bank
    {
        return $bank->load(['translations', 'media']);
    }

    /**
     * @return Collection<int, Bank>
     */
    public function getAllForDropdown(): Collection
    {
        /** @var Collection<int, Bank> */
        return LookupCache::rememberForeverForLocale(
            'banks:dropdown',
            app()->getLocale(),
            fn (): Collection => Bank::query()
                ->with(['translation', 'media'])
                ->where('is_active', true)
                ->get(),
        );
    }

    /**
     * @return Collection<int, Bank>
     */
    public function listForSelect(?string $search = null): Collection
    {
        if (filled($search)) {
            return Bank::query()
                ->withTranslation()
                ->with('media')
                ->where('is_active', true)
                ->when($search, fn ($query, $v) => TranslationSearch::apply($query, (string) $v, 'normalized_name'))
                ->get();
        }

        /** @var Collection<int, Bank> */
        return LookupCache::rememberForeverForLocale(
            'banks:all',
            app()->getLocale(),
            fn (): Collection => Bank::query()
                ->withTranslation()
                ->with('media')
                ->where('is_active', true)
                ->get(),
        );
    }

    public function paginateForApi(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return Bank::query()
            ->with(['translation', 'media'])
            ->where('is_active', true)
            ->when($search, fn ($query, $v) => TranslationSearch::apply($query, (string) $v, 'normalized_name'))
            ->paginate($perPage);
    }
}
