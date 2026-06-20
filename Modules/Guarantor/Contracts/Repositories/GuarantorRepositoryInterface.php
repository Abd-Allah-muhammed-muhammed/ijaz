<?php

namespace Modules\Guarantor\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\DTOs\GuarantorFiltersData;
use Modules\Guarantor\Models\GuarantorRequest;

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

    public function listByRequester(Model $requester, int $perPage = 10): LengthAwarePaginator;

    public function listByCounterparty(Model $counterparty, int $perPage = 10): LengthAwarePaginator;

    public function listForActor(Model $actor, GuarantorFiltersData $filters): LengthAwarePaginator;

    public function listAll(int $perPage = 10): LengthAwarePaginator;
}
