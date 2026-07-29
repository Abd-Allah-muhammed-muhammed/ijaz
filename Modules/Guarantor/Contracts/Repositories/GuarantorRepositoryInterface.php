<?php

namespace Modules\Guarantor\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\Guarantor\DTOs\GuarantorFiltersData;
use Modules\Guarantor\Models\GuarantorRequest;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface GuarantorRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): GuarantorRequest;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(GuarantorRequest $guarantorRequest, array $data): GuarantorRequest;

    public function findById(string $id): GuarantorRequest;

    public function findCounterpartyByPhone(string $phone): ?User;

    public function listByRequester(Model $requester, int $perPage = 10): LengthAwarePaginator;

    public function listByCounterparty(Model $counterparty, int $perPage = 10): LengthAwarePaginator;

    public function listForActor(Model $actor, GuarantorFiltersData $filters): LengthAwarePaginator;

    public function listAll(int $perPage = 10): LengthAwarePaginator;

    public function paginateForDashboard(Request $request, int $perPage): LengthAwarePaginator;

    /**
     * @return array{total: int, pending_admin: int, in_progress: int, overdue: int, ended: int}
     */
    public function getDashboardStats(): array;

    public function delete(GuarantorRequest $guarantorRequest): void;

    public function deleteMedia(Media $media): void;
}
