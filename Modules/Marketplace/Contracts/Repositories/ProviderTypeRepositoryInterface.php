<?php

namespace Modules\Marketplace\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\ProviderType;

interface ProviderTypeRepositoryInterface
{
    public function paginateForDashboard(Request $request): LengthAwarePaginator;

    /**
     * @return Collection<int, ProviderType>
     */
    public function allWithTranslationsForApi(): Collection;

    public function findById(int $id): ProviderType;

    public function create(array $data): ProviderType;

    public function update(ProviderType $providerType, array $data): ProviderType;

    public function delete(ProviderType $providerType): void;

    public function loadForEdit(ProviderType $providerType): ProviderType;

    public function syncCategories(ProviderType $providerType, ?array $categoryIds): void;
}
