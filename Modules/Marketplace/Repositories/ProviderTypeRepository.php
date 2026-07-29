<?php

namespace Modules\Marketplace\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Marketplace\Contracts\Repositories\ProviderTypeRepositoryInterface;
use Modules\Marketplace\Exceptions\MarketplaceException;
use Modules\Marketplace\Models\ProviderType;

class ProviderTypeRepository implements ProviderTypeRepositoryInterface
{
    public function paginateForDashboard(Request $request): LengthAwarePaginator
    {
        return ProviderType::with(['translation'])
            ->withCount('providers')
            ->when($request->input('search'), function ($query, $v) {
                return $query->whereTranslationLike('name', "%{$v}%");
            })
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function allWithTranslationsForApi(): Collection
    {
        return ProviderType::query()
            ->withTranslation()
            ->get();
    }

    public function findById(int $id): ProviderType
    {
        return ProviderType::query()->findOrFail($id);
    }

    public function create(array $data): ProviderType
    {
        return ProviderType::query()->create($data);
    }

    public function update(ProviderType $providerType, array $data): ProviderType
    {
        $providerType->update($data);

        return $providerType->fresh(['translations', 'translation', 'categories.translations']) ?? $providerType;
    }

    public function delete(ProviderType $providerType): void
    {
        if ($providerType->providers()->exists()) {
            throw new MarketplaceException(__('Sorry, unable to execute this action due to existing data'));
        }

        $providerType->delete();
    }

    public function loadForEdit(ProviderType $providerType): ProviderType
    {
        return $providerType->load(['translations', 'categories.translations']);
    }

    public function syncCategories(ProviderType $providerType, ?array $categoryIds): void
    {
        if ($categoryIds !== null) {
            $providerType->categories()->sync($categoryIds);
        } else {
            $providerType->categories()->detach();
        }
    }
}
