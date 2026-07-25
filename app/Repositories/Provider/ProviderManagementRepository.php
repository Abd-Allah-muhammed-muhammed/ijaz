<?php

namespace App\Repositories\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\Models\Provider;
use App\Support\Phone;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class ProviderManagementRepository implements ProviderManagementRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator
    {
        return Provider::query()
            ->with(['providerType', 'wallet', 'latestBlockHistory'])
            ->when($request->input('search'), function ($query, $v) {
                return $query->where(function (Builder $q) use ($v) {
                    $q->where('name', 'like', "%{$v}%")
                        ->orWhere('code', 'like', "%{$v}%");
                });
            })
            ->when($request->input('provider_type_id'), function ($query, $v) {
                return $query->where('provider_type_id', $v);
            })
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    public function create(array $data): Provider
    {
        return Provider::create($data);
    }

    public function update(Provider $provider, array $data): Provider
    {
        $provider->update($data);

        return $provider;
    }

    public function delete(Provider $provider): void
    {
        $provider->delete();
    }

    public function loadForShow(Provider $provider): Provider
    {
        return $provider->load([
            'wallet',
        ]);
    }

    public function loadForEdit(Provider $provider): Provider
    {
        return $provider->load([
            'categories' => function ($query) use ($provider) {
                $query->withTranslation()->with([
                    'providerSkills' => function ($q) use ($provider) {
                        $q->withTranslation()
                            ->where('category_skill.provider_id', $provider->id);
                    },
                ]);
            },
            'providerType',
            'media',
        ]);
    }

    public function loadForApiGet(Provider $provider): Provider
    {
        $provider->load(['categories.translation', 'skills.translation', 'reviews.reviewer']);
        $provider->loadAvg('reviews', 'rating');

        return $provider;
    }

    public function findById(int|string $id): ?Provider
    {
        return Provider::query()->find($id);
    }

    public function findByPhone(Phone $phone, ?int $categoryId = null): ?Provider
    {
        return Provider::query()
            ->with(['categories.translations'])
            ->when(
                $categoryId,
                fn ($query, $v) => $query->whereHas('categories', fn ($q) => $q->where('categories.id', $v))
            )
            ->where('phone', $phone)
            ->first();
    }

    public function saveStatus(Provider $provider, string $status): Provider
    {
        $provider->status = $status;
        $provider->save();

        return $provider;
    }

    public function block(Provider $provider, int $blockDays, ?string $reason): void
    {
        $provider->block($blockDays, $reason);
    }

    public function syncCategories(Provider $provider, array $categoryIds): void
    {
        $provider->categories()->sync($categoryIds);
    }

    public function syncSkills(Provider $provider, array $skills): void
    {
        $provider->skills()->sync($skills);
    }

    public function countAll(): int
    {
        return Provider::query()->count('id');
    }

    /**
     * @return SupportCollection<string, int>
     */
    public function registrationCountsSince(CarbonInterface $since): SupportCollection
    {
        return Provider::query()
            ->where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date');
    }

    /**
     * @return Collection<int, Provider>
     */
    public function latestForDashboard(int $limit = 4): Collection
    {
        return Provider::query()
            ->withCount(['orders', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->limit($limit)
            ->orderByDesc('created_at')
            ->get();
    }
}
