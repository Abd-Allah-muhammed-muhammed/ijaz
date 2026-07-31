<?php

namespace Modules\Classifieds\Repositories;

use App\Models\User;
use App\Support\TranslationSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Classifieds\Contracts\Repositories\CarAdvisementRepositoryInterface;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\QueryFilters\CarAdvisementFilters;

final class CarAdvisementRepository implements CarAdvisementRepositoryInterface
{
    public function getUserAdvisements(User $user, CarAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = $user->carAdvisements()->getQuery();
        $query = $filters->apply($query);

        return $query
            ->with([
                'carBrand',
                'carType',
                'carCategory',
                'city',
                'region',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function getPublishedAdvisements(CarAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = CarAdvisement::query()->published();
        $query = $filters->apply($query);

        return $query
            ->with([
                'carBrand',
                'carType',
                'carCategory',
                'city',
                'region',
                'user',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function create(array $data): CarAdvisement
    {
        return CarAdvisement::query()->create($data);
    }

    public function update(CarAdvisement $model, array $data): CarAdvisement
    {
        $model->update($data);

        return $model;
    }

    public function paginateForDashboard(Request $request): LengthAwarePaginator
    {
        return CarAdvisement::query()
            ->when($request->search, function ($query, $search) {
                $term = TranslationSearch::term((string) $search) ?? (string) $search;
                $query->where(function ($query) use ($search, $term) {
                    $query->where('normalized_title', 'like', "%{$term}%")
                        ->orWhere('normalized_description', 'like', "%{$term}%")
                        ->orWhere('id', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($query, $v) => $query->where('status', $v))
            ->when($request->operation, fn ($query, $v) => $query->where('operation', $v))
            ->when($request->usage_status, fn ($query, $v) => $query->where('usage_status', $v))
            ->when($request->car_brand_id, fn ($query, $v) => $query->where('car_brand_id', $v))
            ->when($request->car_type_id, fn ($query, $v) => $query->where('car_type_id', $v))
            ->when($request->car_category_id, fn ($query, $v) => $query->where('car_category_id', $v))
            ->when($request->city_id, fn ($query, $v) => $query->where('city_id', $v))
            ->when($request->region_id, fn ($query, $v) => $query->where('region_id', $v))
            ->with([
                'carBrand',
                'carType',
                'carCategory',
                'city',
                'region',
                'user',
            ])
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }
}
