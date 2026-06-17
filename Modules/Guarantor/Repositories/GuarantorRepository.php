<?php

namespace Modules\Guarantor\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Models\GuarantorRequest;

class GuarantorRepository implements GuarantorRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): GuarantorRequest
    {
        return GuarantorRequest::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(GuarantorRequest $guarantorRequest, array $data): GuarantorRequest
    {
        $guarantorRequest->update($data);

        return $guarantorRequest->fresh();
    }

    public function findById(string $id): GuarantorRequest
    {
        return GuarantorRequest::query()
            ->with([
                'requester',
                'counterparty',
                'installments',
                'companyDetail.region',
                'companyDetail.city',
                'statusHistories.actor',
                'media',
            ])
            ->findOrFail($id);
    }

    public function listByRequester(Model $requester, int $perPage = 10): LengthAwarePaginator
    {
        return GuarantorRequest::query()
            ->where('requester_type', $requester::class)
            ->where('requester_id', $requester->getKey())
            ->with(['requester', 'counterparty', 'installments', 'media'])
            ->withCount(['installments'])
            ->latest()
            ->paginate($perPage);
    }

    public function listByCounterparty(Model $counterparty, int $perPage = 10): LengthAwarePaginator
    {
        return GuarantorRequest::query()
            ->where('counterparty_type', $counterparty::class)
            ->where('counterparty_id', $counterparty->getKey())
            ->with(['requester', 'counterparty', 'installments', 'media'])
            ->withCount(['installments'])
            ->latest()
            ->paginate($perPage);
    }

    public function listForActor(Model $actor, int $perPage = 10): LengthAwarePaginator
    {
        return GuarantorRequest::query()
            ->forActor($actor)
            ->with(['requester', 'counterparty', 'installments', 'media'])
            ->withCount(['installments'])
            ->latest()
            ->paginate($perPage);
    }

    public function listAll(int $perPage = 10): LengthAwarePaginator
    {
        return GuarantorRequest::query()
            ->with(['requester', 'counterparty', 'installments', 'media'])
            ->withCount(['installments'])
            ->latest()
            ->paginate($perPage);
    }
}
