<?php

namespace Modules\Guarantor\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Guarantor\Actions\Installment\PayInstallmentAction;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Throwable;

class GuarantorInstallmentService
{
    public function __construct(
        private readonly InstallmentRepositoryInterface $repository,
        private readonly PayInstallmentAction $payAction,
        private readonly ReleaseInstallmentAction $releaseAction,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws Throwable
     */
    public function pay(
        GuarantorRequest $request,
        GuarantorInstallment $installment,
        Model $actor,
    ): array {
        return $this->payAction->handle($request, $installment, $actor);
    }

    /**
     * @throws Throwable
     */
    public function release(
        GuarantorInstallment $installment,
        string $trigger = 'payment',
    ): void {
        $this->releaseAction->handle($installment, $trigger);
    }

    public function getNextPending(GuarantorRequest $request): ?GuarantorInstallment
    {
        return $this->repository->getNextPendingForRequest($request);
    }

    public function listForRequest(GuarantorRequest $request): Collection
    {
        return $this->repository->listOrderedForRequest($request);
    }
}
