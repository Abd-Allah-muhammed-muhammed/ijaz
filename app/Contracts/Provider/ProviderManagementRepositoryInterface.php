<?php

namespace App\Contracts\Provider;

use App\Models\Provider;
use App\Support\Phone;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;

interface ProviderManagementRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Provider;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Provider $provider, array $data): Provider;

    public function delete(Provider $provider): void;

    public function loadForShow(Provider $provider): Provider;

    public function loadForEdit(Provider $provider): Provider;

    public function loadForApiGet(Provider $provider): Provider;

    public function findById(int|string $id): ?Provider;

    public function findByPhone(Phone $phone, ?int $categoryId = null): ?Provider;

    public function saveStatus(Provider $provider, string $status): Provider;

    public function block(Provider $provider, int $blockDays, ?string $reason): void;

    /**
     * @param  list<int|string>  $categoryIds
     */
    public function syncCategories(Provider $provider, array $categoryIds): void;

    /**
     * @param  list<array{category_id: int|string, skill_id: int|string}>  $skills
     */
    public function syncSkills(Provider $provider, array $skills): void;

    public function countAll(): int;

    /**
     * @return SupportCollection<string, int>
     */
    public function registrationCountsSince(CarbonInterface $since): SupportCollection;

    /**
     * @return Collection<int, Provider>
     */
    public function latestForDashboard(int $limit = 4): Collection;
}
