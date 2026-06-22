<?php

namespace Modules\Guarantor\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\DTOs\GuarantorFiltersData;
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

    public function listForActor(Model $actor, GuarantorFiltersData $filters): LengthAwarePaginator
    {
        return GuarantorRequest::query()
            ->when(true, function ($q) use ($actor, $filters) {
                if ($filters->role === 'requester') {
                    return $q->where('requester_type', $actor::class)
                        ->where('requester_id', $actor->getKey());
                }

                if ($filters->role === 'counterparty') {
                    return $q->where('counterparty_type', $actor::class)
                        ->where('counterparty_id', $actor->getKey());
                }

                return $q->forActor($actor);
            })
            ->when($filters->statuses, fn ($q) => $q->whereIn('status', $filters->statuses))
            ->when($filters->type, fn ($q, $v) => $q->where('type', $v))
            ->when($filters->search, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->when($filters->date_from, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters->date_to, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->with(['requester', 'counterparty', 'installments', 'media'])
            ->withCount(['installments'])
            ->latest()
            ->paginate($filters->per_page);
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
