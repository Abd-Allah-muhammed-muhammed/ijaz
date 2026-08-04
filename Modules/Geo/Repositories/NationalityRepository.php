<?php

namespace Modules\Geo\Repositories;

use App\Support\LookupCache;
use App\Support\TranslationSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Geo\Contracts\Repositories\NationalityRepositoryInterface;
use Modules\Geo\Exceptions\GeoException;
use Modules\Geo\Models\Nationality;

class NationalityRepository implements NationalityRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return Nationality::query()
            ->with(['translation'])
            ->when(
                $request->input('search'),
                fn (Builder $query, mixed $value) => TranslationSearch::apply($query, (string) $value, 'normalized_name')
            )
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function findById(int $id): Nationality
    {
        return Nationality::query()->findOrFail($id);
    }

    public function create(array $translations): Nationality
    {
        return Nationality::query()->create([
            'translations' => $translations,
        ]);
    }

    public function update(Nationality $nationality, array $translations): Nationality
    {
        $nationality->update([
            'translations' => $translations,
        ]);

        return $nationality->fresh(['translations', 'translation']) ?? $nationality;
    }

    public function delete(Nationality $nationality): void
    {
        if ($nationality->users()->exists()) {
            throw new GeoException(__('dashboard.nationalities.delete_error'));
        }

        $nationality->delete();
    }

    /**
     * @return Collection<int, Nationality>
     */
    public function listForSelect(?string $search = null): Collection
    {
        if (filled($search)) {
            return Nationality::query()->withTranslation()
                ->when($search, fn ($query, $v) => TranslationSearch::apply($query, (string) $v, 'normalized_name'))
                ->get();
        }

        /** @var Collection<int, Nationality> */
        return LookupCache::rememberForeverForLocale(
            'nationalities:all',
            app()->getLocale(),
            fn (): Collection => Nationality::query()->withTranslation()->get(),
        );
    }

    public function paginateForApi(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return Nationality::query()
            ->withTranslation()
            ->when($search, fn ($query, $v) => TranslationSearch::apply($query, (string) $v, 'normalized_name'))
            ->paginate($perPage);
    }
}
