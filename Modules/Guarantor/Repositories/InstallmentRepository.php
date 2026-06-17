<?php

namespace Modules\Guarantor\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

class InstallmentRepository implements InstallmentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): GuarantorInstallment
    {
        return GuarantorInstallment::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(GuarantorInstallment $installment, array $data): GuarantorInstallment
    {
        $installment->update($data);

        return $installment->fresh();
    }

    public function findById(string $id): GuarantorInstallment
    {
        return GuarantorInstallment::query()
            ->with(['guarantorRequest'])
            ->findOrFail($id);
    }

    public function getPendingForRequest(GuarantorRequest $request): Collection
    {
        return $request->installments()
            ->pending()
            ->orderBy('order')
            ->get();
    }

    public function getNextPendingForRequest(GuarantorRequest $request): ?GuarantorInstallment
    {
        return $request->installments()
            ->pending()
            ->orderBy('order')
            ->first();
    }

    public function getOverdue(): LazyCollection
    {
        return GuarantorInstallment::query()
            ->overdue()
            ->with(['guarantorRequest.requester', 'guarantorRequest.counterparty'])
            ->lazyById();
    }
}
