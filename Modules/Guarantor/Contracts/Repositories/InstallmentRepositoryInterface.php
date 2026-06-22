<?php

namespace Modules\Guarantor\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

interface InstallmentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): GuarantorInstallment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(GuarantorInstallment $installment, array $data): GuarantorInstallment;

    public function findById(string $id): GuarantorInstallment;

    public function getPendingForRequest(GuarantorRequest $request): Collection;

    public function listOrderedForRequest(GuarantorRequest $request): Collection;

    public function getNextPendingForRequest(GuarantorRequest $request): ?GuarantorInstallment;

    public function getOverdue(): LazyCollection;
}
