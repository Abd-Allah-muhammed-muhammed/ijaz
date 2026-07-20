<?php

namespace Modules\Geo\Repositories;

use App\Services\Normalize\Normalize;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
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
            ->when($request->input('search'), function (Builder $query, mixed $value) {
                $normalized = Normalize::make((string) $value, app()->getLocale());

                return $query->whereTranslationLike('normalized_name', "%{$normalized}%");
            })
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
}
