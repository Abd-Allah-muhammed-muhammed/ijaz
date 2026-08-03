<?php

namespace Modules\Opportunity\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\LazyCollection;
use Modules\Opportunity\Contracts\Repositories\OpportunityRepositoryInterface;
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

    public function listPublic(?Model $actor = null, int $perPage = 10): LengthAwarePaginator
    {
        return Opportunity::query()
            ->with(['author', 'region.translation', 'city.translation', 'media'])
            ->withCount([
                'offers' => fn (Builder $query) => $this->constrainOffersCountForViewer($query, $actor),
                'comments',
            ])
            ->active()
            ->latest()
            ->paginate($perPage);
    }

    public function listByActor(Model $actor, int $perPage = 10): LengthAwarePaginator
    {
        return Opportunity::query()
            ->byActor($actor)
            ->with(['author', 'region.translation', 'city.translation', 'media'])
            ->withCount([
                'offers' => fn (Builder $query) => $this->constrainOffersCountForViewer($query, $actor),
                'comments',
            ])
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

    public function getExpired(int $chunkSize = 100): LazyCollection
    {
        return Opportunity::expired()
            ->with(['author'])
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
