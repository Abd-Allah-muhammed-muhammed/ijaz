<?php

namespace Modules\Opportunity\Repositories;

use App\Support\LookupCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\LazyCollection;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Models\Opportunity;

class OpportunityRepository implements OpportunityRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Opportunity
    {
        return Opportunity::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Opportunity $opportunity, array $data): Opportunity
    {
        $opportunity->update($data);

        return $opportunity;
    }

    public function findById(string $id): Opportunity
    {
        return Opportunity::query()->findOrFail($id);
    }

    public function findForUpdate(Opportunity $opportunity): Opportunity
    {
        return Opportunity::query()
            ->whereKey($opportunity->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function listPublic(?Model $actor = null, int $perPage = 10, ?int $regionId = null, ?int $cityId = null): LengthAwarePaginator
    {
        return Opportunity::query()
            ->with(['author', 'region.translation', 'city.translation', 'media'])
            ->withCount([
                'offers' => fn (Builder $query) => $this->constrainOffersCountForViewer($query, $actor),
                'comments',
            ])
            ->when($regionId, fn (Builder $query, int $value) => $query->where('region_id', $value))
            ->when($cityId, fn (Builder $query, int $value) => $query->where('city_id', $value))
            ->active()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @param  array<int, string>|null  $statuses
     */
    public function listByActor(Model $actor, int $perPage = 10, ?array $statuses = null): LengthAwarePaginator
    {
        return Opportunity::query()
            ->byActor($actor)
            ->with(['author', 'region.translation', 'city.translation', 'media'])
            ->withCount([
                'offers' => fn (Builder $query) => $this->constrainOffersCountForViewer($query, $actor),
                'comments',
            ])
            ->when($statuses, fn (Builder $query, array $values) => $query->whereIn('status', $values))
            ->latest()
            ->paginate($perPage);
    }

    public function loadForShow(Opportunity $opportunity, ?Model $actor = null): Opportunity
    {
        $opportunity->load([
            'author',
            'region.translation',
            'city.translation',
            'acceptedOffer.author',
            'media',
        ]);
        $opportunity->loadCount([
            'offers' => fn (Builder $query) => $this->constrainOffersCountForViewer($query, $actor),
            'comments',
        ]);

        return $opportunity;
    }

    public function paginateForDashboard(Request $request): LengthAwarePaginator
    {
        return Opportunity::query()
            ->with(['author', 'region.translation', 'city.translation'])
            ->withCount(['offers', 'comments'])
            ->when($request->search, fn ($query) => $query->where('title', 'like', "%{$request->search}%"))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->latest()
            ->paginate($request->integer('per_page', 10))
            ->withQueryString();
    }

    /**
     * @return array{total: int, pending_admin: int}
     */
    public function getDashboardStats(): array
    {
        /** @var array{total: int, pending_admin: int} */
        return LookupCache::rememberFor('stats:opportunity:dashboard', 30, fn (): array => [
            'total' => Opportunity::query()->count(),
            'pending_admin' => Opportunity::query()
                ->where('status', OpportunityStatusEnum::PendingAdmin)
                ->count(),
        ]);
    }

    public function getExpired(int $chunkSize = 100): LazyCollection
    {
        return Opportunity::expired()
            ->with(['author'])
            ->lazyById($chunkSize);
    }

    public function getMissingExpiry(int $chunkSize = 100): LazyCollection
    {
        return Opportunity::query()
            ->whereNull('expires_at')
            ->lazyById($chunkSize);
    }

    public function delete(Opportunity $opportunity): void
    {
        $opportunity->delete();
    }

    /**
     * Scope the offers withCount/loadCount subquery to what the viewer may see.
     *
     * Per-row logic (opportunity author varies by row), expressed as a single OR:
     * - viewer is null → count nothing (0)
     * - viewer owns this opportunity row → include every offer for that row
     * - otherwise → include only offers authored by the viewer
     *
     * Parent columns are referenced inside the correlated withCount subquery so
     * authorship is evaluated per opportunity, not as one static filter.
     */
    private function constrainOffersCountForViewer(Builder $query, ?Model $actor): void
    {
        if ($actor === null) {
            $query->whereRaw('0 = 1');

            return;
        }

        $actorType = $actor::class;
        $actorId = $actor->getKey();
        $opportunitiesTable = (new Opportunity)->getTable();
        $offersTable = $query->getModel()->getTable();

        $query->where(function (Builder $constraint) use ($actorType, $actorId, $opportunitiesTable, $offersTable): void {
            $constraint
                ->where(function (Builder $ownsOpportunity) use ($actorType, $actorId, $opportunitiesTable): void {
                    $ownsOpportunity
                        ->where("{$opportunitiesTable}.author_type", $actorType)
                        ->where("{$opportunitiesTable}.author_id", $actorId);
                })
                ->orWhere(function (Builder $ownsOffer) use ($actorType, $actorId, $offersTable): void {
                    $ownsOffer
                        ->where("{$offersTable}.author_type", $actorType)
                        ->where("{$offersTable}.author_id", $actorId);
                });
        });
    }
}
