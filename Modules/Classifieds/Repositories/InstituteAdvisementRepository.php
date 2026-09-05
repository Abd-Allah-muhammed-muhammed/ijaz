<?php

namespace Modules\Classifieds\Repositories;

use App\Models\User;
use App\Support\TranslationSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Classifieds\Contracts\Repositories\InstituteAdvisementRepositoryInterface;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\QueryFilters\InstituteAdvisementFilters;

final class InstituteAdvisementRepository implements InstituteAdvisementRepositoryInterface
{
    public function getUserAdvisements(User $user, InstituteAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = $user->instituteAdvisements()->getQuery();
        $query = $filters->apply($query);

        return $query
            ->with([
                'specialization.translations',
                'city.translations',
                'region.translations',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function getPublishedAdvisements(InstituteAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = InstituteAdvisement::query()->published();
        $query = $filters->apply($query);

        return $query
            ->with([
                'specialization.translations',
                'city.translations',
                'region.translations',
                'user',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function getPublishedAdvisementsForUser(User $user, InstituteAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = $user->instituteAdvisements()->getQuery()->published();
        $query = $filters->apply($query);

        return $query
            ->with([
                'specialization.translations',
                'city.translations',
                'region.translations',
                'user',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function create(array $data): InstituteAdvisement
    {
        return InstituteAdvisement::query()->create($data);
    }

    public function update(InstituteAdvisement $model, array $data): InstituteAdvisement
    {
        $model->update($data);

        return $model;
    }

    public function paginateForDashboard(Request $request): LengthAwarePaginator
    {
        return InstituteAdvisement::query()
            ->when($request->search, function ($query, $search) {
                $term = TranslationSearch::term((string) $search) ?? (string) $search;
                $query->where(function ($query) use ($search, $term) {
                    $query->where('normalized_title', 'like', "%{$term}%")
                        ->orWhere('normalized_description', 'like', "%{$term}%")
                        ->orWhere('id', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($query, $v) => $query->where('status', $v))
            ->when($request->type, fn ($query, $v) => $query->where('type', $v))
            ->when($request->study_type, fn ($query, $v) => $query->where('study_type', $v))
            ->when($request->study_level, fn ($query, $v) => $query->where('study_level', $v))
            ->when($request->specialization_id, fn ($query, $v) => $query->where('specialization_id', $v))
            ->when($request->city_id, fn ($query, $v) => $query->where('city_id', $v))
            ->when($request->region_id, fn ($query, $v) => $query->where('region_id', $v))
            ->with([
                'specialization.translations',
                'city.translations',
                'region.translations',
                'user',
            ])
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }
}
