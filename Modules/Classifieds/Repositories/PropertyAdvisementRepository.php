<?php

namespace Modules\Classifieds\Repositories;

use App\Models\User;
use App\Support\TranslationSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Classifieds\Contracts\Repositories\PropertyAdvisementRepositoryInterface;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Classifieds\QueryFilters\PropertyAdvisementFilters;

final class PropertyAdvisementRepository implements PropertyAdvisementRepositoryInterface
{
    public function getUserAdvisements(User $user, PropertyAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = $user->propertyAdvisements()->getQuery();

        $query = $filters->apply($query);

        return $query
            ->with([
                'propertyType.translation',
                'city.translation',
                'region.translation',
                'category.translation',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    public function getPublishedAdvisements(PropertyAdvisementFilters $filters): LengthAwarePaginator
    {
        $query = PropertyAdvisement::query()->published();

        $query = $filters->apply($query);

        return $query
            ->with([
                'propertyType.translation',
                'city.translation',
                'region.translation',
                'category.translation',
                'user',
                'media',
            ])
            ->latest()
            ->paginate($filters->perPage());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PropertyAdvisement
    {
        return PropertyAdvisement::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PropertyAdvisement $model, array $data): PropertyAdvisement
    {
        $model->update($data);

        return $model;
    }

    public function paginateForDashboard(Request $request): LengthAwarePaginator
    {
        return PropertyAdvisement::query()
            ->when($request->search, function ($query, $search) {
                $term = TranslationSearch::term((string) $search) ?? (string) $search;
                $query->where(function ($query) use ($search, $term) {
                    $query->where('normalized_title', 'like', "%{$term}%")
                        ->orWhere('normalized_description', 'like', "%{$term}%")
                        ->orWhere('license', 'like', "%{$search}%")
                        ->orWhere('id', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($query, $v) => $query->where('status', $v))
            ->when($request->operation, fn ($query, $v) => $query->where('operation', $v))
            ->when($request->facade, fn ($query, $v) => $query->where('facade', $v))
            ->when($request->street_width, fn ($query, $v) => $query->where('street_width', $v))
            ->when($request->street_type, fn ($query, $v) => $query->where('street_type', $v))
            ->when($request->property_type_id, fn ($query, $v) => $query->where('property_type_id', $v))
            ->when($request->city_id, fn ($query, $v) => $query->where('city_id', $v))
            ->when($request->region_id, fn ($query, $v) => $query->where('region_id', $v))
            ->when($request->category_id, fn ($query, $v) => $query->where('category_id', $v))
            ->with([
                'propertyType',
                'city',
                'region',
                'category',
                'user',
            ])
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }
}
