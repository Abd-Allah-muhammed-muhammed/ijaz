<?php

namespace Modules\Classifieds\Repositories;

use App\Models\User;
use App\Support\TranslationSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Classifieds\Contracts\Repositories\ElectronicAdvisementRepositoryInterface;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\QueryFilters\ElectronicAdvisementFilters;

final class ElectronicAdvisementRepository implements ElectronicAdvisementRepositoryInterface
{
    public function getUserAdvisements(User $user, ElectronicAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = $user->electronicAdvisements()->getQuery();
        $query = $filters->apply($query);

        return $query
            ->with([
                'deviceCategory',
                'electronicBrand',
                'city',
                'region',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function getPublishedAdvisements(ElectronicAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = ElectronicAdvisement::query()->published();
        $query = $filters->apply($query);

        return $query
            ->with([
                'deviceCategory',
                'electronicBrand',
                'city',
                'region',
                'user',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function getPublishedAdvisementsForUser(User $user, ElectronicAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = $user->electronicAdvisements()->getQuery()->published();
        $query = $filters->apply($query);

        return $query
            ->with([
                'deviceCategory.translations',
                'electronicBrand.translations',
                'city.translations',
                'region.translations',
                'user',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function create(array $data): ElectronicAdvisement
    {
        return ElectronicAdvisement::query()->create($data);
    }

    public function update(ElectronicAdvisement $model, array $data): ElectronicAdvisement
    {
        $model->update($data);

        return $model;
    }

    public function paginateForDashboard(Request $request): LengthAwarePaginator
    {
        return ElectronicAdvisement::query()
            ->when($request->search, function ($query, $search) {
                $term = TranslationSearch::term((string) $search) ?? (string) $search;
                $query->where(function ($query) use ($search, $term) {
                    $query->where('normalized_title', 'like', "%{$term}%")
                        ->orWhere('normalized_description', 'like', "%{$term}%")
                        ->orWhere('id', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($query, $v) => $query->where('status', $v))
            ->when($request->condition, fn ($query, $v) => $query->where('condition', $v))
            ->when($request->device_category_id, fn ($query, $v) => $query->where('device_category_id', $v))
            ->when($request->electronic_brand_id, fn ($query, $v) => $query->where('electronic_brand_id', $v))
            ->when($request->city_id, fn ($query, $v) => $query->where('city_id', $v))
            ->when($request->region_id, fn ($query, $v) => $query->where('region_id', $v))
            ->with([
                'deviceCategory',
                'electronicBrand',
                'city',
                'region',
                'user',
            ])
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }
}
