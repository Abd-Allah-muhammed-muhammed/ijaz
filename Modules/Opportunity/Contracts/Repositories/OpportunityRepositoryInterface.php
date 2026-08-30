<?php

namespace Modules\Opportunity\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\LazyCollection;
use Modules\Opportunity\Models\Opportunity;

interface OpportunityRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Opportunity;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Opportunity $opportunity, array $data): Opportunity;

    public function findById(string $id): Opportunity;

    public function findForUpdate(Opportunity $opportunity): Opportunity;

    public function listPublic(?Model $actor = null, int $perPage = 10, ?int $regionId = null, ?int $cityId = null): LengthAwarePaginator;

    /**
     * @param  array<int, string>|null  $statuses
     */
    public function listByActor(Model $actor, int $perPage = 10, ?array $statuses = null): LengthAwarePaginator;

    public function loadForShow(Opportunity $opportunity, ?Model $actor = null): Opportunity;

    public function paginateForDashboard(Request $request): LengthAwarePaginator;

    /**
     * @return array{total: int, pending_admin: int}
     */
    public function getDashboardStats(): array;

    public function getExpired(int $chunkSize = 100): LazyCollection;

    public function getMissingExpiry(int $chunkSize = 100): LazyCollection;

    public function delete(Opportunity $opportunity): void;
}
