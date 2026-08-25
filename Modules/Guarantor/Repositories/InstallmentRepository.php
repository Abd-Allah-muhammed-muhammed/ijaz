<?php

namespace Modules\Guarantor\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
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

    public function listOrderedForRequest(GuarantorRequest $request): Collection
    {
        return $request->installments()
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

    public function findPreviousPaidInstallment(
        GuarantorRequest $request,
        int $currentOrder,
    ): ?GuarantorInstallment {
        return $request->installments()
            ->where('order', $currentOrder - 1)
            ->where('status', InstallmentStatusEnum::Paid)
            ->first();
    }

    public function refresh(GuarantorInstallment $installment): GuarantorInstallment
    {
        return $installment->refresh();
    }

    public function getOverdue(): LazyCollection
    {
        return GuarantorInstallment::query()
            ->overdue()
            ->whereHas('guarantorRequest', fn ($query) => $query->whereNotIn('status', [
                GuarantorStatusEnum::Ended->value,
                GuarantorStatusEnum::Cancelled->value,
                GuarantorStatusEnum::Escalated->value,
                GuarantorStatusEnum::Settled->value,
                GuarantorStatusEnum::Disputed->value,
            ]))
            ->with(['guarantorRequest.requester', 'guarantorRequest.counterparty'])
            ->lazyById();
    }

    public function voidPendingOrOverdueForRequest(GuarantorRequest $request): int
    {
        return $request->installments()
            ->whereIn('status', [
                InstallmentStatusEnum::Pending,
                InstallmentStatusEnum::Overdue,
            ])
            ->update([
                'status' => InstallmentStatusEnum::Voided,
            ]);
    }
}
