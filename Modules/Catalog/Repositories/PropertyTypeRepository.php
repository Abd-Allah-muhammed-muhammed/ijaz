<?php

namespace Modules\Catalog\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Catalog\Contracts\Repositories\PropertyTypeRepositoryInterface;
use Modules\Catalog\Models\PropertyType;
use Modules\Catalog\QueryFilters\PropertyType\PropertyTypeFilters;

class PropertyTypeRepository implements PropertyTypeRepositoryInterface
{
    public function paginate(PropertyTypeFilters $filters): LengthAwarePaginator
    {
        return $filters->apply(PropertyType::query()->with(['translations']))
            ->paginate($filters->perPage())
            ->withQueryString();
    }

    public function paginateForDashboard(Request $request): LengthAwarePaginator
    {
        return PropertyType::with(['translation'])
            ->when($request->input('search'), function ($query, $v) {
                return $query->whereTranslationLike('name', "%{$v}%");
            })
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function findById(int $id): PropertyType
    {
        return PropertyType::query()->findOrFail($id);
    }

    public function create(array $data): PropertyType
    {
        return PropertyType::query()->create($data);
    }

    public function update(PropertyType $propertyType, array $data): PropertyType
    {
        $propertyType->update($data);

        return $propertyType->fresh(['translations', 'translation']) ?? $propertyType;
    }

    public function delete(PropertyType $propertyType): void
    {
        $propertyType->delete();
    }

    public function loadForEdit(PropertyType $propertyType): PropertyType
    {
        return $propertyType->load(['translations']);
    }
}
